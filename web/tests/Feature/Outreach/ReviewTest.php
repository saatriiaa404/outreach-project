<?php

namespace Tests\Feature\Outreach;

use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\User;
use App\Support\OutreachDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function draft(string $sourceCode = 'BAZNAS', array $attributes = []): Outreach
    {
        return Outreach::factory()
            ->for(OutreachSource::factory()->create(['code' => $sourceCode, 'name' => $sourceCode]), 'source')
            ->for(OutreachType::factory()->create(['name' => 'Rutilahu']), 'type')
            ->create(['outreach_date' => '2026-10-27', ...$attributes])
            ->refresh();
    }

    /**
     * @return array<string, string|null>
     */
    private function values(array $document, string $section): array
    {
        $rows = collect($document['sections'])->firstWhere('title', $section)['rows'];

        return collect($rows)->mapWithKeys(fn (array $row) => [$row['label'] => $row['value']])->all();
    }

    public function test_the_document_follows_the_template_and_the_display_formats()
    {
        $outreach = $this->draft(attributes: [
            'nama' => 'Warga Demo',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '2006-06-15',
            'desil_1_10' => 9,
            'status_kesejahteraan' => 'Sejahtera',
            'merokok' => false,
            'bantuan_pernah_diterima' => false,
            'punya_hutang' => false,
            'penghasilan_kepala_keluarga' => 1_500_000,
            'penghasilan_suami_istri' => 0,
            'penghasilan_anak_1kk' => 0,
            'penghasilan_lainnya' => 250_000,
            'pengeluaran_sewa' => 0,
            'jumlah_sepeda_angin' => 1,
            'jumlah_sepeda_listrik' => 0,
            'jumlah_becak' => 0,
            'jumlah_sepeda_motor' => 2,
            'jumlah_roda_4' => 0,
            'jumlah_kendaraan_lainnya' => 1,
            'nama_kendaraan_lainnya' => 'Gerobak',
            'mck_layak' => 'KURANG_LAYAK',
        ]);

        $document = OutreachDocument::build($outreach);

        $this->assertSame('LAPORAN HASIL OUTREACH', $document['title']);
        $this->assertSame('Rutilahu', $document['type']);
        $this->assertSame('Berdasarkan hasil outreach yang telah dilaksanakan pada hari Selasa tanggal 27 Oktober 2026 Bersama ini kami laporkan sebagai berikut :', $document['opening']);
        $this->assertNotContains('BAZNAS', $document['letterhead'], 'The Source is not printed (outreach.md 8.2).');

        $klien = $this->values($document, 'DATA KLIEN');
        $this->assertCount(23, $klien);
        $this->assertSame('Surabaya, 15 Juni 2006 / 20 tahun', $klien['Tempat dan Tanggal Lahir / Umur']);
        $this->assertSame('Sejahtera (Desil 9)', $klien['Status Kesejahteraan']);
        $this->assertSame('Tidak', $klien['Apakah Klien merokok']);
        $this->assertSame('Tidak ada', $klien['Bantuan yang pernah diterima']);
        $this->assertSame('-', $klien['Berapa besar pinjaman / hutang'], 'Not applicable without a debt.');
        $this->assertNull($klien['Agama'], 'Not filled is never shown as "-".');

        $pendapatan = $this->values($document, 'PENDAPATAN KELUARGA DALAM SEBULAN');
        $this->assertSame('Rp 1.500.000', $pendapatan['Pendapatan Kepala Keluarga']);
        $this->assertSame('-', $pendapatan['Pendapatan Suami / Istri']);
        $this->assertSame('Rp 1.750.000', $pendapatan['Total Pendapatan Keluarga']);

        $pengeluaran = $this->values($document, 'PENGELUARAN KELUARGA DALAM SEBULAN');
        $this->assertSame('-', $pengeluaran['Biaya sewa/kos/kontrak rumah']);
        $this->assertNull($pengeluaran['Biaya PDAM']);
        $this->assertNull($pengeluaran['Total Pengeluaran Keluarga'], 'The total stays open while a component is empty.');

        $rumah = $this->values($document, 'KONDISI RUMAH DAN ASET YANG DIMILIKI');
        $this->assertSame('Sepeda angin 1, Sepeda motor 2, Gerobak 1', $rumah['Jumlah dan Jenis kendaraan yang dimiliki']);
        $this->assertSame('Kurang layak', $rumah['Fasilitas MCK']);
    }

    public function test_vehicles_read_tidak_ada_when_all_are_zero()
    {
        $outreach = $this->draft(attributes: array_fill_keys(['jumlah_sepeda_angin', 'jumlah_sepeda_listrik', 'jumlah_becak', 'jumlah_sepeda_motor', 'jumlah_roda_4', 'jumlah_kendaraan_lainnya'], 0));

        $this->assertSame('Tidak ada', $this->values(OutreachDocument::build($outreach), 'KONDISI RUMAH DAN ASET YANG DIMILIKI')['Jumlah dan Jenis kendaraan yang dimiliki']);
    }

    public function test_review_lists_what_finalisasi_needs_per_step()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'review']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('outreach/Review')
                ->where('ready', false)
                ->has('checklist', 9)
                ->where('checklist.0.key', 'identitas')
                ->where('checklist.0.missing', [])
                ->where('checklist.5.key', 'mustahik')
                ->has('checklist.5.missing', 31)
                ->where('checklist.8.key', 'tanda_tangan')
                ->where('checklist.8.missing', ['Tanda tangan Petugas', 'Tanda tangan Warga'])
                ->has('document.sections', 4)
                ->has('mustahik.answers', 25)
                ->has('kifayah.fields', 15)
                ->has('signatures', 2)
                ->where('steps.9.key', 'review')
                ->where('steps.9.state', 'incomplete'));
    }

    public function test_bangga_surabaya_review_has_no_forms_or_signatures()
    {
        $outreach = $this->draft('BANGGA_SURABAYA');

        $this->actingAs($outreach->petugas)
            ->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'review']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('checklist', 6)
                ->where('checklist.5.key', 'lampiran')
                ->where('mustahik', null)
                ->where('kifayah', null)
                ->where('signatures', []));
    }

    public function test_review_is_only_for_the_owner_while_editable()
    {
        $outreach = $this->draft();

        $this->actingAs(User::factory()->create())
            ->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'review']))
            ->assertForbidden();
    }
}
