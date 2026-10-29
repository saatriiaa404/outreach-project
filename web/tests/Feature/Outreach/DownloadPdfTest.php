<?php

namespace Tests\Feature\Outreach;

use App\Actions\Outreach\ApproveOutreach;
use App\Actions\Outreach\FinalizeOutreach;
use App\Models\Outreach;
use App\Models\OutreachVersion;
use App\Models\User;
use App\Support\OutreachPdf;
use Database\Seeders\DemoSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class DownloadPdfTest extends TestCase
{
    use BuildsReadyOutreach;
    use RefreshDatabase;

    private User $kepala;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
        $this->kepala = User::factory()->kepala()->create();
        DemoSeeder::signatureTemplate($this->kepala);
    }

    private function submitted(string $sourceCode = 'BAZNAS'): Outreach
    {
        $outreach = $this->ready($sourceCode);
        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);

        return $outreach->refresh();
    }

    private function approved(string $sourceCode = 'BAZNAS'): Outreach
    {
        $outreach = $this->submitted($sourceCode);
        app(ApproveOutreach::class)->handle($outreach, $this->kepala, (int) $outreach->latestVersion()->value('id'));

        return $outreach->refresh();
    }

    private function pdfUrl(Outreach $outreach, string $document): string
    {
        return route('outreaches.versions.pdf', [$outreach, $outreach->latestVersion()->value('id'), $document]);
    }

    public function test_kepala_and_admin_download_each_required_pdf_of_the_approved_version()
    {
        $outreach = $this->approved();

        foreach ([$this->kepala, User::factory()->admin()->create()] as $user) {
            foreach (['outreach', 'mustahik', 'kifayah'] as $document) {
                $response = $this->actingAs($user)->get($this->pdfUrl($outreach, $document))
                    ->assertOk()
                    ->assertHeader('Content-Type', 'application/pdf')
                    ->assertHeader('Content-Disposition', "attachment; filename=\"{$outreach->outreach_number}-v1-{$document}.pdf\"");

                $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
            }
        }
    }

    public function test_petugas_never_downloads_not_even_their_own()
    {
        $outreach = $this->approved();

        $this->actingAs($outreach->petugas)->get($this->pdfUrl($outreach, 'outreach'))->assertForbidden();
        $this->actingAs($outreach->petugas)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page->where('downloads', []));
    }

    public function test_only_approved_versions_and_documents_of_the_package_are_served()
    {
        $pending = $this->submitted();
        $this->actingAs($this->kepala)->get($this->pdfUrl($pending, 'outreach'))->assertForbidden();

        $bangga = $this->approved('BANGGA_SURABAYA');
        $this->actingAs($this->kepala)->get($this->pdfUrl($bangga, 'outreach'))->assertOk();
        $this->actingAs($this->kepala)->get($this->pdfUrl($bangga, 'mustahik'))->assertNotFound();

        $baznas = $this->approved();
        $foreignVersion = $bangga->latestVersion()->value('id');
        $this->actingAs($this->kepala)->get(route('outreaches.versions.pdf', [$baznas, $foreignVersion, 'outreach']))->assertNotFound();
    }

    public function test_the_review_page_lists_the_downloads_for_kepala()
    {
        $outreach = $this->approved();
        $versionId = OutreachVersion::query()->sole()->id;

        $this->actingAs($this->kepala)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->has('downloads', 3)
                ->where('downloads.0.title', 'PDF Outreach (dengan lampiran)')
                ->where('downloads.0.url', route('outreaches.versions.pdf', [$outreach, $versionId, 'outreach'])));

        $this->actingAs($this->kepala)->get(route('outreaches.show', $this->submitted()))
            ->assertInertia(fn (Assert $page) => $page->where('downloads', []));
    }

    public function test_a_failed_render_shows_an_error_instead_of_a_partial_file()
    {
        $outreach = $this->approved();
        $this->partialMock(OutreachPdf::class, fn ($mock) => $mock->shouldReceive('render')->andThrow(new RuntimeException('Dompdf crashed')));

        $this->actingAs($this->kepala)->get($this->pdfUrl($outreach, 'kifayah'))
            ->assertRedirect(route('outreaches.show', $outreach))
            ->assertSessionHasErrors(['pdf' => 'PDF Kifayah tidak dapat dibuat saat ini. Coba lagi; bila tetap gagal, hubungi Admin.']);
    }

    public function test_the_download_is_rendered_from_the_version_and_its_approval()
    {
        $outreach = $this->approved();
        $outreach->forceFill(['nama' => 'Nama Setelah Pengesahan'])->save();
        $this->kepala->forceFill(['name' => 'Nama Kepala Baru'])->save();

        $rendered = [];
        $this->partialMock(OutreachPdf::class, function ($mock) use (&$rendered) {
            $mock->shouldReceive('render')->andReturnUsing(function (OutreachVersion $version, string $document, ?array $approval) use (&$rendered) {
                $rendered = [$version->snapshot_data['client']['nama'], $approval['name'] ?? null];

                return '%PDF-1.7';
            });
        });

        $this->actingAs($this->kepala)->get($this->pdfUrl($outreach, 'outreach'))->assertOk();

        $this->assertSame('Warga Demo', $rendered[0], 'Content comes from the approved version.');
        // The Kepala name and NIP are read at render time (database.md 4.1).
        $this->assertSame('Nama Kepala Baru', $rendered[1]);
    }
}
