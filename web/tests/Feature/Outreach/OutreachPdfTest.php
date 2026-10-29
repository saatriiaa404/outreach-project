<?php

namespace Tests\Feature\Outreach;

use App\Actions\Outreach\FinalizeOutreach;
use App\Enums\AttachmentCategory;
use App\Models\DocumentTemplateVersion;
use App\Models\MustahikAnswer;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\OutreachVersion;
use App\Support\OutreachDocument;
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
        $this->assertSame(5, substr_count($html, 'src="data:image/'), 'The logo and the attachments are embedded, never linked.');
        $this->assertLessThan(strpos($html, 'Lampiran: Kartu Keluarga'), strpos($html, 'Lampiran: KTP'), 'Attachments follow the category order.');
    }

    public function test_a_pdf_attachment_prints_as_a_reference_page_and_the_document_still_renders()
    {
        $outreach = $this->ready();
        $file = $this->file();
        $file->forceFill(['mime_type' => 'application/pdf', 'extension' => 'pdf', 'original_name' => 'surat-keterangan.pdf'])->save();
        (new OutreachAttachment)->forceFill([
            'outreach_id' => $outreach->id, 'file_id' => $file->id, 'category' => AttachmentCategory::Tambahan, 'title' => 'Surat keterangan',
        ])->save();
        $version = app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->refresh()->edit_version);
        $pdf = app(OutreachPdf::class);

        $html = $pdf->html($version, 'outreach', ['date' => '28 Oktober 2026', 'name' => 'KEPALA SINTETIS', 'nip' => '1', 'signature' => null]);

        $this->assertSame(5, substr_count($html, 'class="attachment"'));
        $this->assertStringContainsString('Lampiran berupa berkas PDF dengan nama <strong>surat-keterangan.pdf</strong>', $html);
        $this->assertSame(5, substr_count($html, 'src="data:image/'), 'The PDF is referenced, not embedded.');
        $this->assertStringStartsWith('%PDF-', $pdf->render($version, 'outreach'));
    }

    public function test_template_v3_follows_the_source_forms_and_older_versions_keep_their_layout()
    {
        $version = $this->finalized();
        $pdf = app(OutreachPdf::class);

        $this->assertSame(3, $version->documentTemplate->version_number);
        $outreach = $pdf->html($version, 'outreach');
        $this->assertStringContainsString('<div class="kop"><img src="data:image/png;base64,', $outreach);
        $this->assertStringContainsString('Jenis Outreach : '.OutreachDocument::typeLabel($version->snapshot_data['meta']['type']['name']), $outreach);
        // Money rows keep the form's order although MySQL reorders the JSON keys.
        $this->assertLessThan(strpos($outreach, 'Biaya BBM / bensin'), strpos($outreach, 'Biaya sewa/kos/kontrak rumah'));
        $this->assertLessThan(strpos($outreach, 'Pendapatan lainnya'), strpos($outreach, 'Pendapatan Kepala Keluarga'));
        $this->assertStringContainsString('size: legal portrait', $pdf->html($version, 'mustahik'));
        $this->assertStringContainsString('<div class="logo"><img src="data:image/jpeg;base64,', $pdf->html($version, 'kifayah'));

        // Versions finalized earlier render with the template they recorded.
        $template = fn (int $number) => DocumentTemplateVersion::query()->where('template_key', $version->documentTemplate->template_key)->where('version_number', $number)->sole();
        $this->assertFalse($template(2)->is_active);
        $version->forceFill(['document_template_version_id' => $template(2)->id])->saveQuietly();
        $v2 = $version->refresh();
        $this->assertStringContainsString('<td class="lh-logo"><img src="data:image/png;base64,', $pdf->html($v2, 'outreach'));
        $this->assertStringNotContainsString('Jenis Outreach :', $pdf->html($v2, 'outreach'));

        $version->forceFill(['document_template_version_id' => $template(1)->id])->saveQuietly();
        $v1 = $version->refresh();
        $this->assertStringNotContainsString('class="lh-logo"', $pdf->html($v1, 'outreach'));
        $this->assertStringNotContainsString('class="logo"', $pdf->html($v1, 'kifayah'));
        $this->assertStringNotContainsString('☑', $pdf->html($v1, 'kifayah'));
    }

    public function test_bantuan_is_added_to_the_type_only_once()
    {
        $this->assertSame('Bantuan Alat Kesehatan', OutreachDocument::typeLabel('Alat Kesehatan'));
        $this->assertSame('Bantuan Modal Usaha', OutreachDocument::typeLabel('Bantuan Modal Usaha'));
        $this->assertSame('Bantuan Bantuanku', OutreachDocument::typeLabel('Bantuanku'));
    }

    public function test_the_kifayah_date_follows_the_outreach_date_not_the_print_date()
    {
        $this->travelTo('2027-02-03 10:00');
        $version = $this->finalized();

        $html = app(OutreachPdf::class)->html($version, 'kifayah');

        $this->assertStringContainsString('Surabaya, 27 Oktober 2026', $html);
        $this->assertStringNotContainsString('2027', $html);
    }

    public function test_the_mustahik_pdf_checks_only_the_chosen_option_in_the_score_column()
    {
        $version = $this->finalized();

        $html = app(OutreachPdf::class)->html($version, 'mustahik');

        $this->assertStringContainsString('FORM SURVEY MUSTAHIK', $html);
        $this->assertSame(25, substr_count($html, '<span class="check">✓</span>'), 'One check per question (D-053).');
        $this->assertStringContainsString('Surabaya, 27 Oktober 2026', $html, 'Survey date above the Surveyor signature.');
        $this->assertSame(1, substr_count($html, 'src="data:image/png'));
    }

    public function test_the_kifayah_pdf_uses_corrected_labels_ticks_expenses_and_keeps_literal_options()
    {
        $outreach = $this->ready();
        $outreach->kifayahForm->forceFill(['expense_head_household' => 750000, 'expense_school_sd' => 150000])->save();
        $version = app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->refresh()->edit_version);

        $html = app(OutreachPdf::class)->html($version, 'kifayah');

        $this->assertStringContainsString('Ibu Menyusui', $html);
        $this->assertStringContainsString('PENGELUARAN TAMBAHAN PER BULAN', $html);
        $this->assertStringContainsString('Pendidikan Tinggi', $html);
        $this->assertSame(2, substr_count($html, '☑'), 'Only amounts above 0 are ticked (VAL-006).');
        $this->assertSame(11, substr_count($html, '☐'), 'Ten 0 expenses and the education box stay empty.');
        $this->assertSame(7, substr_count($html, '<td class="b check">✔</td>'));
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
        $this->assertMatchesRegularExpression('#sangat Kecil \(&lt;4x4\)</td>\s*<td class="q-score"><span class="check">✓#', $pdf->html($version, 'mustahik'));
    }

    public function test_documents_outside_the_template_package_are_rejected()
    {
        $this->expectException(InvalidArgumentException::class);

        app(OutreachPdf::class)->html($this->finalized('BANGGA_SURABAYA'), 'mustahik');
    }
}
