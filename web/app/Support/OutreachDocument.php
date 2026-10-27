<?php

namespace App\Support;

use App\Models\Outreach;
use App\Models\OutreachAttachment;

/**
 * The logical Outreach document shared by the browser preview (Workflow v2.1
 * section 17.3) and, later, the PDF (R7.2). Structure and labels follow the
 * blank template (docs/reference/blank_outreach.pdf, outreach.md section 8);
 * value formats follow D-050. A value of null means "not filled" and is
 * never shown as "-"; "-" only marks an explicit "none" (outreach.md 6).
 */
class OutreachDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Outreach $outreach): array
    {
        $date = $outreach->outreach_date;

        return [
            'letterhead' => config('app.letterhead'),
            'title' => 'LAPORAN HASIL OUTREACH',
            // Type under the title; the Source is not printed (outreach.md 8.2).
            'type' => $outreach->type->name,
            'opening' => "Berdasarkan hasil outreach yang telah dilaksanakan pada hari {$date->translatedFormat('l')} tanggal {$date->translatedFormat('j F Y')} Bersama ini kami laporkan sebagai berikut :",
            'sections' => [
                self::klien($outreach),
                self::familyMoney($outreach, 'PENDAPATAN KELUARGA DALAM SEBULAN', 'pendapatan', 'Total Pendapatan Keluarga'),
                self::familyMoney($outreach, 'PENGELUARAN KELUARGA DALAM SEBULAN', 'pengeluaran', 'Total Pengeluaran Keluarga'),
                self::rumah($outreach),
            ],
            'narratives' => [
                ['title' => 'UPAYA YANG TELAH DILAKUKAN', 'value' => self::text($outreach, 'upaya_dilakukan')],
                ['title' => 'HARAPAN KLIEN', 'value' => self::text($outreach, 'harapan_klien')],
                ['title' => 'URAIAN DETAIL MENGAPA KLIEN DIUSULKAN INTERVENSI INI', 'value' => self::text($outreach, 'uraian_alasan_intervensi')],
            ],
            // Filled at approval with the approving Kepala (outreach.md 8.4).
            'approval' => ['lines' => ['Mengetahui', 'Lurah Manukan Kulon']],
            'attachments' => $outreach->attachments()->with('file')->orderBy('category')->orderBy('sort_order')->get()
                ->map(fn (OutreachAttachment $attachment) => [
                    'label' => $attachment->title ?: $attachment->category->label(),
                    'name' => $attachment->file->original_name,
                    'is_image' => $attachment->file->isImage(),
                    'url' => route('outreaches.attachments.show', [$outreach, $attachment]),
                ])->values()->all(),
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
     * @return array{title: string, rows: list<array{no: int|null, label: string, value: string|null, total?: bool}>}
     */
    private static function klien(Outreach $outreach): array
    {
        $fields = OutreachFormSchema::steps()['klien']['fields'];
        $value = fn (string $name) => self::text($outreach, $name);
        $yesNo = fn (string $name) => match ($outreach->getAttribute($name)) {
            true => 'Ya',
            false => 'Tidak',
            default => null,
        };

        $rows = [
            'Nama' => $value('nama'),
            'NIK' => $value('nik'),
            'Tempat dan Tanggal Lahir / Umur' => self::birth($outreach),
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
            $fields['penghasilan_klien']['label'] => self::rupiah($outreach->getAttribute('penghasilan_klien')),
            'No. telp yang bisa dihubungi' => $value('no_telp'),
            'Status Perkawinan' => $value('status_perkawinan'),
            'Pendidikan terakhir' => $value('pendidikan_terakhir'),
            'Status Kesejahteraan' => $outreach->desil_1_10 === null
                ? null
                : "{$outreach->getAttribute('status_kesejahteraan')} (Desil {$outreach->desil_1_10})",
            'Jaminan Kesehatan yang dimiliki' => $value('jaminan_kesehatan'),
            'Apakah Klien merokok' => $yesNo('merokok'),
            // D-027: the description, or "Tidak ada" when no aid was received.
            'Bantuan yang pernah diterima' => match ($outreach->getAttribute('bantuan_pernah_diterima')) {
                false => 'Tidak ada',
                true => $value('bantuan_detail'),
                default => null,
            },
            'Apakah klien punya pinjaman / hutang' => $yesNo('punya_hutang'),
            // Not applicable without a debt (outreach.md 6).
            'Berapa besar pinjaman / hutang' => match ($outreach->getAttribute('punya_hutang')) {
                false => '-',
                true => self::rupiah($outreach->getAttribute('besar_hutang')),
                default => null,
            },
        ];

        return ['title' => 'DATA KLIEN', 'rows' => self::numbered($rows)];
    }

    /**
     * @return array{title: string, rows: list<array{no: int|null, label: string, value: string|null, total?: bool}>}
     */
    private static function familyMoney(Outreach $outreach, string $title, string $group, string $totalLabel): array
    {
        $rows = [];

        foreach (OutreachFormSchema::steps()['keluarga']['fields'] as $name => $field) {
            if (($field['group'] ?? null) === $group) {
                $rows[$field['label']] = self::rupiah($outreach->getAttribute($name));
            }
        }

        return ['title' => $title, 'rows' => [
            ...self::numbered($rows),
            ['no' => null, 'label' => $totalLabel, 'value' => self::rupiah(OutreachFormSchema::total($outreach, $group)), 'total' => true],
        ]];
    }

    /**
     * @return array{title: string, rows: list<array{no: int|null, label: string, value: string|null, total?: bool}>}
     */
    private static function rumah(Outreach $outreach): array
    {
        $mck = $outreach->getAttribute('mck_layak');

        $rows = [
            'Ukuran / Luas bangunan rumah' => self::text($outreach, 'luas_bangunan'),
            'Jenis lantai' => self::text($outreach, 'jenis_lantai'),
            'Jenis dinding' => self::text($outreach, 'jenis_dinding'),
            'Jenis atap bangunan' => self::text($outreach, 'jenis_atap'),
            'Daya listrik' => self::text($outreach, 'daya_listrik'),
            'Sumber air' => self::text($outreach, 'sumber_air'),
            'Fasilitas MCK' => $mck === null ? null : OutreachFormSchema::MCK_OPTIONS[$mck] ?? null,
            'Jumlah dan Jenis kendaraan yang dimiliki' => self::vehicles($outreach),
            'Perhiasan' => self::text($outreach, 'perhiasan'),
            'Aset lainnya' => self::text($outreach, 'aset_lainnya'),
        ];

        return ['title' => 'KONDISI RUMAH DAN ASET YANG DIMILIKI', 'rows' => self::numbered($rows)];
    }

    /**
     * "Surabaya, 15 Juni 2006 / 20 tahun" (D-045, D-050); parts not yet
     * filled are left out, and nothing is shown while both are empty.
     */
    private static function birth(Outreach $outreach): ?string
    {
        $place = self::text($outreach, 'tempat_lahir');
        $date = $outreach->tanggal_lahir?->translatedFormat('j F Y');
        $age = $outreach->ageOnOutreachDate();

        if ($place === null && $date === null) {
            return null;
        }

        return implode(', ', array_filter([$place, $date])).($age === null ? '' : " / {$age} tahun");
    }

    /**
     * One line from the vehicle counts above 0 (D-020, D-050), or null while
     * any count is still empty.
     */
    private static function vehicles(Outreach $outreach): ?string
    {
        $parts = [];

        foreach (OutreachFormSchema::steps()['rumah']['fields'] as $name => $field) {
            if (($field['group'] ?? null) !== 'kendaraan' || $field['input'] !== 'integer') {
                continue;
            }

            $count = $outreach->getAttribute($name);

            if ($count === null) {
                return null;
            }

            if ((int) $count > 0) {
                $label = $name === 'jumlah_kendaraan_lainnya'
                    ? (self::text($outreach, 'nama_kendaraan_lainnya') ?? $field['label'])
                    : $field['label'];
                $parts[] = "{$label} {$count}";
            }
        }

        return $parts === [] ? 'Tidak ada' : implode(', ', $parts);
    }

    private static function text(Outreach $outreach, string $name): ?string
    {
        $value = $outreach->getAttribute($name);

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
