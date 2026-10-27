<?php

namespace Tests\Feature\Outreach;

use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Support\OutreachFormSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttachmentRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function draft(string $typeCode = 'RUTILAHU'): Outreach
    {
        return Outreach::factory()
            ->for(OutreachSource::factory()->create(['code' => 'BANGGA_SURABAYA']), 'source')
            ->for(OutreachType::factory()->create(['code' => $typeCode, 'name' => 'Rutilahu']), 'type')
            ->create();
    }

    private function upload(Outreach $outreach, string $category, UploadedFile $file): void
    {
        $this->actingAs($outreach->petugas)
            ->post(route('outreaches.attachments.store', $outreach), ['category' => $category, 'file' => $file])
            ->assertSessionHasNoErrors();
    }

    public function test_finalisasi_needs_the_four_baseline_images_but_not_additional_ones()
    {
        $outreach = $this->draft();

        $this->upload($outreach, 'TAMBAHAN', UploadedFile::fake()->image('tambahan.jpg', 30, 30));
        $this->upload($outreach, 'TAMBAHAN', UploadedFile::fake()->createWithContent('surat.pdf', "%PDF-1.4\n%%EOF\n"));
        $this->assertSame(['KTP', 'Kartu Keluarga (KK)', 'Foto Klien', 'Foto Rumah'], OutreachFormSchema::missingAttachments($outreach));

        foreach (['KTP', 'KK', 'FOTO_KLIEN'] as $i => $category) {
            $this->upload($outreach, $category, UploadedFile::fake()->image("{$i}.jpg", 10 + $i, 10));
        }
        $this->assertSame(['Foto Rumah'], OutreachFormSchema::missingAttachments($outreach));

        $this->upload($outreach, 'FOTO_RUMAH', UploadedFile::fake()->image('rumah.jpg', 20, 20));
        $this->assertSame([], OutreachFormSchema::missingAttachments($outreach));
        $this->assertArrayNotHasKey('lampiran', OutreachFormSchema::missingForFinalization($outreach));
    }

    public function test_a_removed_baseline_attachment_counts_as_missing_again()
    {
        $outreach = $this->draft();
        $this->upload($outreach, 'KTP', UploadedFile::fake()->image('ktp.jpg'));

        $this->delete(route('outreaches.attachments.destroy', [$outreach, OutreachAttachment::query()->sole()]));

        $this->assertContains('KTP', OutreachFormSchema::missingAttachments($outreach));
    }

    public function test_the_lampiran_step_shows_blockers_and_the_type_suggestion()
    {
        $outreach = $this->draft();
        $this->upload($outreach, 'KK', UploadedFile::fake()->image('kk.jpg'));

        $this->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'lampiran']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('missingForFinalization', ['KTP', 'Foto Klien', 'Foto Rumah'])
                ->where('additionalHint', 'Foto tambahan rumah; dapat lebih dari satu'));
    }

    public function test_a_type_without_a_known_suggestion_shows_none()
    {
        $outreach = $this->draft('JENIS_LAIN');

        $this->actingAs($outreach->petugas)
            ->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'lampiran']))
            ->assertInertia(fn (Assert $page) => $page->where('additionalHint', null));
    }
}
