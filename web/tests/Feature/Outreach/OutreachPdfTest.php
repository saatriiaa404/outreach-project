<?php

namespace Tests\Feature\Outreach;

use App\Actions\Outreach\FinalizeOutreach;
use App\Models\MustahikAnswer;
use App\Models\Outreach;
use App\Models\OutreachVersion;
use App\Support\OutreachPdf;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class OutreachPdfTest extends TestCase
{
    use BuildsReadyOutreach;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
    }

    private function finalized(string $sourceCode = 'BAZNAS'): OutreachVersion
    {
        $outreach = $this->ready($sourceCode);

        return app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);
    }

    public function test_baznas_versions_have_three_documents_and_bangga_one()
    {
        $pdf = app(OutreachPdf::class);

        $this->assertSame(['outreach', 'mustahik', 'kifayah'], $pdf->documents($this->finalized()));
        $this->assertSame(['outreach'], $pdf->documents($this->finalized('BANGGA_SURABAYA')));
    }

    public function test_every_document_renders_to_a_pdf()
    {
        $version = $this->finalized();
        $pdf = app(OutreachPdf::class);

        foreach ($pdf->documents($version) as $document) {
            $this->assertStringStartsWith('%PDF-', $pdf->render($version, $document), $document);
        }
    }

    public function test_the_outreach_pdf_follows_the_template_with_attachment_pages()
    {
        $version = $this->finalized();

        $html = app(OutreachPdf::class)->html($version, 'outreach', [
            'date' => '28 Oktober 2026', 'name' => 'KEPALA SINTETIS', 'nip' => '000000000000000001', 'signature' => null,
        ]);

        $this->assertStringContainsString('LAPORAN HASIL OUTREACH', $html);
        $this->assertStringContainsString('pada hari Selasa tanggal 27 Oktober 2026', $html);
        $this->assertStringNotContainsString('Baznas', $html, 'The Source is not printed on the Outreach PDF.');
        $this->assertStringContainsString('Surabaya, 28 Oktober 2026', $html);
        $this->assertStringContainsString('NIP. 000000000000000001', $html);
        $this->assertSame(4, substr_count($html, 'class="attachment"'));
        $this->assertSame(4, substr_count($html, 'src="data:image/'), 'Attachments are embedded, never linked.');
        $this->assertLessThan(strpos($html, 'Lampiran: Kartu Keluarga'), strpos($html, 'Lampiran: KTP'), 'Attachments follow the category order.');
    }

    public function test_the_mustahik_pdf_checks_only_the_chosen_option_in_the_score_column()
    {
        $version = $this->finalized();

        $html = app(OutreachPdf::class)->html($version, 'mustahik');

        $this->assertStringContainsString('FORM SURVEY MUSTAHIK', $html);
        $this->assertSame(25, substr_count($html, '<td class="q-score">✓</td>'), 'One check per question (D-053).');
        $this->assertStringContainsString('Surabaya, 27 Oktober 2026', $html, 'Survey date above the Surveyor signature.');
        $this->assertSame(1, substr_count($html, 'src="data:image/png'));
    }

    public function test_the_kifayah_pdf_uses_corrected_labels_empty_expense_boxes_and_literal_options()
    {
        $version = $this->finalized();

        $html = app(OutreachPdf::class)->html($version, 'kifayah');

        $this->assertStringContainsString('Ibu Menyusui', $html);
        $this->assertStringContainsString('PENGELUARAN TAMBAHAN PER BULAN', $html);
        $this->assertStringContainsString('Pendidikan Tinggi', $html);
        $this->assertSame(13, substr_count($html, '☐'), 'Twelve expense boxes and the education box stay empty.');
        $this->assertSame(7, substr_count($html, '<td class="check" style="border: 1px solid #111">✔</td>'));
        $this->assertStringContainsString('Memiliki Hutang', $html);
        $this->assertStringContainsString('Rp. -', $html, 'A 0 amount reads "-" (D-050).');
    }

    public function test_pdfs_come_from_the_version_not_the_current_data()
    {
        $version = $this->finalized();
        $outreach = Outreach::query()->sole();
        $outreach->forceFill(['nama' => 'Nama Setelah Finalisasi'])->save();
        MustahikAnswer::query()->update(['answer' => ['option' => '4']]);

        $pdf = app(OutreachPdf::class);

        $this->assertStringContainsString('Warga Demo', $pdf->html($version, 'kifayah'));
        $this->assertStringNotContainsString('Nama Setelah Finalisasi', $pdf->html($version, 'outreach'));
        $this->assertStringContainsString('sangat Kecil (&lt;4x4)</td>
                                    <td class="q-score">✓', $pdf->html($version, 'mustahik'));
    }

    public function test_documents_outside_the_template_package_are_rejected()
    {
        $this->expectException(InvalidArgumentException::class);

        app(OutreachPdf::class)->html($this->finalized('BANGGA_SURABAYA'), 'mustahik');
    }
}
