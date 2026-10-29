<?php

namespace App\Support;

use App\Enums\AttachmentCategory;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use Illuminate\Support\Carbon;

/**
 * The logical Outreach document shared by the browser preview (Workflow v2.1
 * section 17.3) and the PDF (R7.2). It is always built from snapshot_data
 * (D-051): the Draft preview from a snapshot of the current data, a
 * historical document from the stored version. Structure and labels follow
 * the blank template (docs/reference/blank_outreach.pdf, outreach.md 8);
 * value formats follow D-050. A value of null means "not filled" and is
 * never shown as "-"; "-" only marks an explicit "none" (outreach.md 6).
 */
class OutreachDocument
{
    /**
     * Preview of the current (Draft) data with the current attachments.
     *
     * @return array<string, mixed>
     */
    public static function build(Outreach $outreach): array
    {
        $attachments = $outreach->attachments()->with('file')->orderBy('sort_order')->get()
            ->sortBy(fn ($attachment) => array_search($attachment->category, AttachmentCategory::cases(), true), SORT_NUMERIC)
            ->map(fn (OutreachAttachment $attachment) => [
                'label' => $attachment->title ?: $attachment->category->label(),
                'name' => $attachment->file->original_name,
                'is_image' => $attachment->file->isImage(),
                'url' => route('outreaches.attachments.show', [$outreach, $attachment]),
            ])->values()->all();

        return self::fromSnapshot(OutreachSnapshot::build($outreach), $attachments);
    }

    /**
     * @param  array<string, mixed>  $snapshot  snapshot_data, schema version 1
     * @param  array<int, array{label: string, name: string, is_image: bool, url: string}>  $attachments
     * @return array<string, mixed>
     */
    public static function fromSnapshot(array $snapshot, array $attachments): array
    {
        $date = Carbon::parse($snapshot['meta']['outreach_date']);

        return [
            'letterhead' => config('app.letterhead'),
            'title' => 'LAPORAN HASIL OUTREACH',
            // Type under the title; the Source is not printed (outreach.md 8.2).
            'type' => $snapshot['meta']['type']['name'],
            'opening' => "Berdasarkan hasil outreach yang telah dilaksanakan pada hari {$date->translatedFormat('l')} tanggal {$date->translatedFormat('j F Y')} Bersama ini kami laporkan sebagai berikut :",
            'sections' => [
                self::klien($snapshot),
                self::money($snapshot['family_income'], 'PENDAPATAN KELUARGA DALAM SEBULAN', 'Total Pendapatan Keluarga'),
                self::money($snapshot['expenses'], 'PENGELUARAN KELUARGA DALAM SEBULAN', 'Total Pengeluaran Keluarga'),
                self::rumah($snapshot),
            ],
            'narratives' => [
                ['title' => 'UPAYA YANG TELAH DILAKUKAN', 'value' => self::text($snapshot['narrative']['upaya_dilakukan'])],
                ['title' => 'HARAPAN KLIEN', 'value' => self::text($snapshot['narrative']['harapan_klien'])],
                ['title' => 'URAIAN DETAIL MENGAPA KLIEN DIUSULKAN INTERVENSI INI', 'value' => self::text($snapshot['narrative']['uraian_alasan_intervensi'])],
            ],
            // Filled at approval with the approving Kepala (outreach.md 8.4).
            'approval' => ['lines' => ['Mengetahui', 'Lurah Manukan Kulon']],
            'attachments' => $attachments,
        ];
    }

    public static function rupiah(?int $amount): ?string
    {
        return match (true) {
            $amount === null => null,
            $amount === 0 => '-',
            default => 'Rp '.number_format($amount, 0, ',', '.'),
        };
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{title: string, rows: list<array{no: int|null, label: string, value: string|null, total?: bool}>}
     */
    private static function klien(array $snapshot): array
    {
        $client = $snapshot['client'];
        $value = fn (string $name) => self::text($client[$name] ?? null);
        $yesNo = fn (string $name) => match ($client[$name] ?? null) {
            true => 'Ya',
            false => 'Tidak',
            default => null,
        };
        $status = $client['status_kesejahteraan'];

        $rows = [
            'Nama' => $value('nama'),
            'NIK' => $value('nik'),
            'Tempat dan Tanggal Lahir / Umur' => self::birth($client, $snapshot['meta']['outreach_date']),
            'Agama' => $value('agama'),
            'Alamat KTP' => $value('alamat_ktp'),
            'Alamat Domisili' => $value('alamat_domisili'),
            'Status Kependudukan' => $value('status_kependudukan'),
            'Menjadi warga Surabaya sejak' => $value('tahun_warga_surabaya'),
            'Pekerjaan' => $value('pekerjaan'),
            'Status Hubungan dalam Keluarga' => $value('status_hubungan_keluarga'),
            'Jumlah anggota keluarga dalam 1 KK' => $value('jumlah_anggota_kk'),
            'Jumlah KK dalam 1 rumah' => $value('jumlah_kk_dalam_rumah'),
            'Status tempat tinggal' => $value('status_tempat_tinggal'),
            'Penghasilan klien sebulan' => self::rupiah($client['penghasilan_klien']),
            'No. telp yang bisa dihubungi' => $value('no_telp'),
            'Status Perkawinan' => $value('status_perkawinan'),
            'Pendidikan terakhir' => $value('pendidikan_terakhir'),
            'Status Kesejahteraan' => $status === null ? null : "{$status['label']} (Desil {$status['desil']})",
            'Jaminan Kesehatan yang dimiliki' => $value('jaminan_kesehatan'),
            'Apakah Klien merokok' => $yesNo('merokok'),
            // D-027: the description, or "Tidak ada" when no aid was received.
            'Bantuan yang pernah diterima' => match ($client['bantuan_pernah_diterima']) {
                false => 'Tidak ada',
                true => $value('bantuan_detail'),
                default => null,
            },
            'Apakah klien punya pinjaman / hutang' => $yesNo('punya_hutang'),
            // Not applicable without a debt (outreach.md 6).
            'Berapa besar pinjaman / hutang' => match ($client['punya_hutang']) {
                false => '-',
                true => self::rupiah($client['besar_hutang']),
                default => null,
            },
        ];

        return ['title' => 'DATA KLIEN', 'rows' => self::numbered($rows)];
    }

    /**
     * Rows labelled as in the stepper, with the derived total that stays open
     * while any component is empty (outreach.md 4.2, 4.3).
     *
     * @param  array<string, int|null>  $values
     * @return array{title: string, rows: list<array{no: int|null, label: string, value: string|null, total?: bool}>}
     */
    private static function money(array $values, string $title, string $totalLabel): array
    {
        $fields = OutreachFormSchema::steps()['keluarga']['fields'];
        $rows = [];

        foreach ($values as $name => $amount) {
            $rows[$fields[$name]['label']] = self::rupiah($amount);
        }

        $total = in_array(null, $values, true) ? null : array_sum($values);

        return ['title' => $title, 'rows' => [
            ...self::numbered($rows),
            ['no' => null, 'label' => $totalLabel, 'value' => self::rupiah($total), 'total' => true],
        ]];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{title: string, rows: list<array{no: int|null, label: string, value: string|null, total?: bool}>}
     */
    private static function rumah(array $snapshot): array
    {
        $house = $snapshot['house'];
        $assets = $snapshot['assets'];

        $rows = [
            'Ukuran / Luas bangunan rumah' => self::text($house['luas_bangunan']),
            'Jenis lantai' => self::text($house['jenis_lantai']),
            'Jenis dinding' => self::text($house['jenis_dinding']),
            'Jenis atap bangunan' => self::text($house['jenis_atap']),
            'Daya listrik' => self::text($house['daya_listrik']),
            'Sumber air' => self::text($house['sumber_air']),
            'Fasilitas MCK' => $house['mck_layak']['label'] ?? null,
            'Jumlah dan Jenis kendaraan yang dimiliki' => self::vehicles($assets),
            'Perhiasan' => self::text($assets['perhiasan']),
            'Aset lainnya' => self::text($assets['aset_lainnya']),
        ];

        return ['title' => 'KONDISI RUMAH DAN ASET YANG DIMILIKI', 'rows' => self::numbered($rows)];
    }

    /**
     * "Surabaya, 15 Juni 2006 / 20 tahun" (D-045, D-050); parts not yet
     * filled are left out, and nothing is shown while both are empty.
     *
     * @param  array<string, mixed>  $client
     */
    private static function birth(array $client, string $outreachDate): ?string
    {
        $place = self::text($client['tempat_lahir']);
        $birth = $client['tanggal_lahir'] === null ? null : Carbon::parse($client['tanggal_lahir']);

        if ($place === null && $birth === null) {
            return null;
        }

        $on = Carbon::parse($outreachDate);
        $age = $birth === null || $birth->gt($on) ? null : (int) $birth->diffInYears($on);

        return implode(', ', array_filter([$place, $birth?->translatedFormat('j F Y')])).($age === null ? '' : " / {$age} tahun");
    }

    /**
     * One line from the vehicle counts above 0 (D-020, D-050), or null while
     * any count is still empty.
     *
     * @param  array<string, mixed>  $assets
     */
    private static function vehicles(array $assets): ?string
    {
        $parts = [];

        foreach (OutreachFormSchema::steps()['rumah']['fields'] as $name => $field) {
            if (($field['group'] ?? null) !== 'kendaraan' || $field['input'] !== 'integer') {
                continue;
            }

            $count = $assets[$name];

            if ($count === null) {
                return null;
            }

            if ((int) $count > 0) {
                $label = $name === 'jumlah_kendaraan_lainnya'
                    ? (self::text($assets['nama_kendaraan_lainnya']) ?? $field['label'])
                    : $field['label'];
                $parts[] = "{$label} {$count}";
            }
        }

        return $parts === [] ? 'Tidak ada' : implode(', ', $parts);
    }

    private static function text(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * @param  array<string, string|null>  $rows
     * @return list<array{no: int, label: string, value: string|null}>
     */
    private static function numbered(array $rows): array
    {
        $numbered = [];

        foreach (array_keys($rows) as $i => $label) {
            $numbered[] = ['no' => $i + 1, 'label' => $label, 'value' => $rows[$label]];
        }

        return $numbered;
    }
}
