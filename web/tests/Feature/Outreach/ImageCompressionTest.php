<?php

namespace Tests\Feature\Outreach;

use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\OutreachSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCompressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function upload(UploadedFile $file, string $category = 'FOTO_RUMAH'): OutreachAttachment
    {
        $outreach = Outreach::factory()
            ->for(OutreachSource::factory()->create(['code' => 'BANGGA_SURABAYA']), 'source')
            ->create();

        $this->actingAs($outreach->petugas)
            ->post(route('outreaches.attachments.store', $outreach), ['category' => $category, 'file' => $file])
            ->assertSessionHasNoErrors();

        return OutreachAttachment::query()->with('file')->sole();
    }

    /**
     * A noisy image compresses like a real photo instead of a flat colour.
     */
    private function photo(int $width, int $height, string $type = 'png'): string
    {
        $image = imagecreatetruecolor($width, $height);
        mt_srand(7);
        for ($i = 0; $i < 200; $i++) {
            imagefilledrectangle($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, 0xFFFFFF));
        }

        ob_start();
        $type === 'png' ? imagepng($image) : imagejpeg($image, null, 95);

        return (string) ob_get_clean();
    }

    /**
     * Adds an EXIF block with only the Orientation tag after the JPEG start marker.
     */
    private function withOrientation(string $jpeg, int $orientation): string
    {
        $tiff = 'II*'."\0".pack('V', 8).pack('v', 1).pack('vvVvv', 0x0112, 3, 1, $orientation, 0).pack('V', 0);
        $app1 = "Exif\0\0".$tiff;

        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($app1) + 2).$app1.substr($jpeg, 2);
    }

    public function test_large_photos_are_scaled_to_2000_px_and_stored_as_jpeg()
    {
        $original = $this->photo(3000, 2000);
        $attachment = $this->upload(UploadedFile::fake()->createWithContent('rumah.png', $original));
        $file = $attachment->file;

        $this->assertSame([2000, 1333], [$file->width, $file->height]);
        $this->assertSame('image/jpeg', $file->mime_type);
        $this->assertSame('jpg', $file->extension);
        $this->assertSame('rumah.png', $file->original_name);
        $this->assertSame(hash('sha256', $original), $file->sha256_hash, 'The hash stays that of the original (D-047).');

        $stored = Storage::disk('local')->get($file->storage_key);
        $this->assertSame($file->size_bytes, strlen($stored));
        $this->assertSame([2000, 1333, IMAGETYPE_JPEG], array_slice(getimagesizefromstring($stored), 0, 3));
        $this->assertCount(1, Storage::disk('local')->allFiles(), 'Only the optimized copy is kept.');
    }

    public function test_small_images_are_not_upscaled()
    {
        $attachment = $this->upload(UploadedFile::fake()->image('klien.jpg', 800, 600), 'FOTO_KLIEN');

        $this->assertSame([800, 600], [$attachment->file->width, $attachment->file->height]);
    }

    public function test_exif_orientation_is_applied_and_metadata_is_dropped()
    {
        // Stored landscape, but the camera says "rotate 90 degrees clockwise".
        $jpeg = $this->withOrientation($this->photo(400, 300, 'jpeg'), 6);
        $this->assertSame(6, exif_read_data('data://image/jpeg;base64,'.base64_encode($jpeg))['Orientation']);

        $attachment = $this->upload(UploadedFile::fake()->createWithContent('ktp.jpg', $jpeg), 'KTP');

        $this->assertSame([300, 400], [$attachment->file->width, $attachment->file->height]);
        $stored = Storage::disk('local')->get($attachment->file->storage_key);
        $this->assertArrayNotHasKey('Orientation', @exif_read_data('data://image/jpeg;base64,'.base64_encode($stored)) ?: []);
    }

    public function test_pdf_attachments_are_stored_unchanged()
    {
        $pdf = "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n";
        $attachment = $this->upload(UploadedFile::fake()->createWithContent('surat.pdf', $pdf), 'TAMBAHAN');

        $this->assertSame($pdf, Storage::disk('local')->get($attachment->file->storage_key));
        $this->assertNull($attachment->file->width);
    }
}
