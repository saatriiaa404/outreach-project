<?php

namespace Tests\Feature\Outreach;

use App\Enums\AttachmentCategory;
use App\Enums\OutreachStatus;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\OutreachSource;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function draft(): Outreach
    {
        return Outreach::factory()
            ->for(OutreachSource::query()->where('code', 'BANGGA_SURABAYA')->first() ?? OutreachSource::factory()->create(['code' => 'BANGGA_SURABAYA']), 'source')
            ->create();
    }

    private function upload(Outreach $outreach, UploadedFile $file, string $category = 'KTP', array $extra = [])
    {
        return $this->actingAs($outreach->petugas)->post(route('outreaches.attachments.store', $outreach), [
            'category' => $category,
            'file' => $file,
            ...$extra,
        ]);
    }

    private function pdf(string $name = 'surat.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
    }

    public function test_an_image_is_stored_privately_with_its_hash_and_attached()
    {
        $outreach = $this->draft();
        $image = UploadedFile::fake()->image('ktp.jpg', 640, 480);
        $hash = hash_file('sha256', $image->getRealPath());

        $this->upload($outreach, $image)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'lampiran']));

        $attachment = OutreachAttachment::query()->with('file')->sole();
        $this->assertSame(AttachmentCategory::Ktp, $attachment->category);
        $this->assertSame($hash, $attachment->file->sha256_hash, 'Hash of the original upload (D-047).');
        $this->assertSame('image/jpeg', $attachment->file->mime_type);
        $this->assertSame([640, 480], [$attachment->file->width, $attachment->file->height]);
        $this->assertStringStartsWith("attachments/{$outreach->id}/", $attachment->file->storage_key);
        Storage::disk('local')->assertExists($attachment->file->storage_key);
    }

    public function test_content_decides_the_type_not_the_file_name()
    {
        $outreach = $this->draft();
        $fake = UploadedFile::fake()->createWithContent('foto.jpg', 'bukan gambar');

        $this->upload($outreach, $fake)->assertSessionHasErrors(['file' => 'KTP harus berupa gambar JPG, PNG, atau WEBP.']);
        $this->assertSame(0, StoredFile::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_baseline_categories_accept_images_only_while_tambahan_accepts_pdf()
    {
        $outreach = $this->draft();

        $this->upload($outreach, $this->pdf(), 'FOTO_RUMAH')->assertSessionHasErrors('file');
        $this->upload($outreach, $this->pdf(), 'TAMBAHAN', ['title' => 'Surat keterangan'])->assertSessionHasNoErrors();

        $attachment = OutreachAttachment::query()->with('file')->sole();
        $this->assertSame('application/pdf', $attachment->file->mime_type);
        $this->assertSame('Surat keterangan', $attachment->title);
    }

    public function test_files_over_ten_megabytes_are_rejected()
    {
        $outreach = $this->draft();

        $this->upload($outreach, UploadedFile::fake()->image('besar.jpg')->size(10 * 1024 + 1))
            ->assertSessionHasErrors(['file' => 'Ukuran berkas maksimal 10 MB.']);
    }

    public function test_identical_content_is_not_attached_twice_to_the_same_outreach()
    {
        $outreach = $this->draft();
        $image = UploadedFile::fake()->image('kk.jpg');

        $this->upload($outreach, $image, 'KK')->assertSessionHasNoErrors();
        $this->upload($outreach, $image, 'FOTO_KLIEN')
            ->assertSessionHasErrors(['file' => 'Berkas yang sama sudah diunggah sebagai Kartu Keluarga (KK).']);

        $this->assertSame(1, StoredFile::query()->count());
        $this->assertCount(1, Storage::disk('local')->allFiles());

        $other = $this->draft();
        $this->upload($other, $image, 'KK')->assertSessionHasNoErrors();
        $this->assertSame(2, StoredFile::query()->count(), 'Duplicates are checked per Outreach.');
    }

    public function test_reuploading_a_removed_file_reactivates_the_old_relation()
    {
        $outreach = $this->draft();
        $image = UploadedFile::fake()->image('rumah.jpg');

        $this->upload($outreach, $image, 'FOTO_RUMAH');
        $attachment = OutreachAttachment::query()->sole();

        $this->delete(route('outreaches.attachments.destroy', [$outreach, $attachment]))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($attachment);

        $this->upload($outreach, $image, 'FOTO_RUMAH')->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted($attachment);
        $this->assertSame(1, OutreachAttachment::withTrashed()->count());
        $this->assertSame(1, StoredFile::query()->count());
    }

    public function test_replacing_an_attachment_removes_the_old_one()
    {
        $outreach = $this->draft();
        $this->upload($outreach, UploadedFile::fake()->image('lama.jpg'), 'FOTO_KLIEN');
        $old = OutreachAttachment::query()->sole();

        $this->upload($outreach, UploadedFile::fake()->image('baru.png'), 'FOTO_KLIEN', ['replaces' => $old->id])
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($old);
        $this->assertSame('baru.png', $outreach->attachments()->with('file')->sole()->file->original_name);
    }

    public function test_attachments_are_served_only_through_the_authorized_route()
    {
        $outreach = $this->draft();
        $this->upload($outreach, UploadedFile::fake()->image('ktp.png'));
        $attachment = OutreachAttachment::query()->sole();
        $url = route('outreaches.attachments.show', [$outreach, $attachment]);

        $this->actingAs($outreach->petugas)->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->kepala()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();

        $other = $this->draft();
        $this->actingAs($other->petugas)->get(route('outreaches.attachments.show', [$other, $attachment]))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get(route('outreaches.attachments.show', [$other, $attachment]))->assertNotFound();
    }

    public function test_only_the_owner_may_change_attachments_and_only_while_editable()
    {
        $outreach = $this->draft();
        $image = UploadedFile::fake()->image('ktp.jpg');

        foreach ([User::factory()->create(), User::factory()->kepala()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->post(route('outreaches.attachments.store', $outreach), ['category' => 'KTP', 'file' => $image])->assertForbidden();
        }

        $outreach->forceFill(['status' => OutreachStatus::MenungguPengesahan])->save();
        $this->upload($outreach, $image)->assertForbidden();

        $outreach->forceFill(['status' => OutreachStatus::PerluPerbaikan])->save();
        $this->upload($outreach, $image)->assertSessionHasNoErrors();
    }

    public function test_the_lampiran_step_lists_attachments_and_tracks_the_four_baseline_ones()
    {
        $outreach = $this->draft();
        foreach (['KTP', 'KK', 'FOTO_KLIEN'] as $i => $category) {
            $this->upload($outreach, UploadedFile::fake()->image("{$i}.jpg", 10 + $i, 10), $category);
        }

        $this->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'lampiran']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('outreach/Attachments')
                ->has('attachments', 3)
                ->has('categories', 5)
                ->where('steps.5.key', 'lampiran')
                ->where('steps.5.state', 'incomplete')
                ->where('steps.5.missing', 1));

        $this->upload($outreach, UploadedFile::fake()->image('rumah.jpg', 20, 20), 'FOTO_RUMAH');

        $this->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'narasi']))
            ->assertInertia(fn (Assert $page) => $page->where('steps.5.state', 'complete'));
    }
}
