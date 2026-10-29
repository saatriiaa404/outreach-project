<?php

namespace Database\Seeders;

use App\Actions\Outreach\ApproveOutreach;
use App\Actions\Outreach\AttachFileToOutreach;
use App\Actions\Outreach\FinalizeOutreach;
use App\Actions\Outreach\SaveKifayah;
use App\Actions\Outreach\SaveMustahik;
use App\Actions\Outreach\SignOutreachForm;
use App\Enums\AttachmentCategory;
use App\Enums\OutreachStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\SignatureTemplate;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\KifayahFormSchema;
use App\Support\MustahikFormSchema;
use App\Support\OutreachFormSchema;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Synthetic demo data from docs/prototype/dummy-data.md. Never contains real
 * residents' data. The demo password comes from DEMO_USER_PASSWORD in the
 * local .env so it is not committed.
 *
 * DEMO-002 (MENUNGGU_PENGESAHAN) and DEMO-004 (SELESAI) are built through the
 * real actions (save, attach, sign, Finalisasi, Approve) so their versions,
 * snapshots, history, and PDFs agree with their status (database.md 13.2).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('app.demo_user_password');

        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set DEMO_USER_PASSWORD (at least 12 characters) in the local .env before seeding demo accounts.');
        }

        $petugas = $this->user('Rina Petugas', 'petugas.demo@example.test', UserRole::Petugas, $password);
        $kepala = $this->user('Budi Kepala', 'kepala.demo@example.test', UserRole::Kepala, $password, '000000000000000001', 'Penata Tk. I');
        $this->signatureTemplate($kepala);
        $this->user('Sari Admin', 'admin.demo@example.test', UserRole::Admin, $password);

        $this->draft($petugas, 'DRAFT-DEMO00000001', 'BAZNAS', 'RUTILAHU', [
            'nama' => 'Warga Demo Satu',
            'nik' => 'DUMMY-NIK-001',
            'alamat_domisili' => 'Jalan Demo No. 1 (alamat sintetis)',
        ]);

        $this->draft($petugas, 'DRAFT-DEMO00000003', 'BANGGA_SURABAYA', 'BANTUAN_MODAL_USAHA', [
            'nama' => 'Warga Demo Tiga',
            'nik' => 'DUMMY-NIK-003',
            'alamat_domisili' => 'Jalan Demo No. 3 (alamat sintetis)',
        ]);

        $this->submitted($petugas, 'DRAFT-DEMO00000002', 'CSR', 'BEASISWA', [
            'nama' => 'Warga Demo Dua',
            'nik' => 'DUMMY-NIK-002',
            'alamat_domisili' => 'Jalan Demo No. 2 (alamat sintetis)',
        ]);

        $completed = $this->submitted($petugas, 'DRAFT-DEMO00000004', 'BAZNAS', 'ALAT_KESEHATAN', [
            'nama' => 'Warga Demo Empat',
            'nik' => 'DUMMY-NIK-004',
            'alamat_domisili' => 'Jalan Demo No. 4 (alamat sintetis)',
        ]);

        if ($completed !== null) {
            app(ApproveOutreach::class)->handle($completed, $kepala, (int) $completed->latestVersion()->value('id'));
        }
    }

    private function user(string $name, string $email, UserRole $role, string $password, ?string $nip = null, ?string $pangkat = null): User
    {
        return User::query()->updateOrCreate(['email' => $email], [
            'name' => $name,
            'password_hash' => $password,
            'nip' => $nip,
            'pangkat' => $pangkat,
            'role' => $role,
            'status' => UserStatus::Active,
        ]);
    }

    /**
     * A synthetic Kepala signature template (a scribble marked DEMO) so
     * Approve can be demonstrated before template management (R9.3).
     */
    public static function signatureTemplate(User $kepala): SignatureTemplate
    {
        $existing = SignatureTemplate::query()->where('head_user_id', $kepala->id)->orderByDesc('version_number')->first();

        if ($existing !== null) {
            return $existing;
        }

        $image = imagecreatetruecolor(600, 200);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        $ink = imagecolorallocate($image, 17, 24, 39);
        $grey = imagecolorallocate($image, 150, 150, 150);

        if ($transparent === false || $ink === false || $grey === false) {
            throw new RuntimeException('Could not draw the demo signature template.');
        }

        imagesavealpha($image, true);
        imagefill($image, 0, 0, $transparent);
        imagesetthickness($image, 4);
        imageline($image, 40, 140, 160, 60, $ink);
        imageline($image, 160, 60, 240, 150, $ink);
        imageline($image, 240, 150, 380, 70, $ink);
        imageline($image, 380, 70, 560, 120, $ink);
        imagestring($image, 5, 270, 170, 'DEMO', $grey);
        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        $key = "signature-templates/kepala-{$kepala->id}-v1.png";
        Storage::disk('local')->put($key, $png);

        return DB::transaction(function () use ($kepala, $key, $png) {
            $file = new StoredFile;
            $file->forceFill([
                'storage_disk' => 'local',
                'storage_key' => $key,
                'original_name' => 'tanda-tangan-kepala-demo.png',
                'mime_type' => 'image/png',
                'extension' => 'png',
                'size_bytes' => strlen($png),
                'sha256_hash' => hash('sha256', $png),
                'width' => 600,
                'height' => 200,
            ])->save();

            $template = new SignatureTemplate;
            $template->forceFill([
                'file_id' => $file->id,
                'head_user_id' => $kepala->id,
                'version_number' => 1,
                'valid_from' => now(),
                'is_active' => true,
            ])->save();

            return $template;
        });
    }

    /**
     * A complete synthetic Outreach finalized by the Petugas; null when it was
     * seeded before.
     *
     * @param  array<string, string>  $client
     */
    private function submitted(User $petugas, string $draftCode, string $source, string $type, array $client): ?Outreach
    {
        if (Outreach::query()->where('draft_code', $draftCode)->exists()) {
            return null;
        }

        $outreach = $this->draft($petugas, $draftCode, $source, $type, $client);
        $values = [];

        foreach (OutreachFormSchema::steps() as $step => $definition) {
            foreach ($definition['fields'] as $name => $field) {
                if ($step !== 'identitas' && $field['required'] && ! array_key_exists($name, $client)) {
                    $values[$name] = match ($field['input']) {
                        'integer', 'money' => 0,
                        'date' => '1980-01-01',
                        'boolean' => false,
                        'select' => array_key_first($field['options']),
                        default => 'Isian demo (sintetis)',
                    };
                }
            }
        }

        $outreach->forceFill([
            ...$values,
            'tempat_lahir' => 'Surabaya',
            'no_telp' => '080000000000',
            'desil_1_10' => 2,
            'status_kesejahteraan' => OutreachFormSchema::DESIL_LABELS[2],
            'penghasilan_klien' => 1500000,
            'pengeluaran_belanja_memasak' => 900000,
            'pengeluaran_listrik' => 100000,
            'harapan_klien' => 'Harapan klien (data sintetis).',
            'uraian_alasan_intervensi' => 'Alasan intervensi untuk demo; seluruh isian adalah data sintetis.',
        ])->save();

        if ($outreach->source->usesMustahikKifayah()) {
            $mustahik = [
                'survey_date' => $outreach->outreach_date->toDateString(),
                'kepala_keluarga_nama' => $client['nama'],
                'kepala_keluarga_nik_no_kk' => 'DUMMY-KK-'.substr($draftCode, -3),
                'kepala_keluarga_alamat' => $client['alamat_domisili'],
                'kepala_keluarga_rt' => '001',
                'kepala_keluarga_rw' => '002',
            ];
            foreach (array_keys(MustahikFormSchema::QUESTIONS) as $i => $code) {
                $options = array_keys(MustahikFormSchema::QUESTIONS[$code]['options']);
                $mustahik[$code] = (string) $options[$i % count($options)];
            }
            app(SaveMustahik::class)->handle($outreach, $petugas, $mustahik, $outreach->refresh()->edit_version);

            $kifayah = [...array_map(fn () => 0, KifayahFormSchema::FIELDS), 'income_head_family' => 1500000, 'expense_head_household' => 750000];
            foreach (array_keys(KifayahFormSchema::QUESTIONS) as $code) {
                $kifayah[$code] = (string) array_key_first(KifayahFormSchema::QUESTIONS[$code]['options']);
            }
            app(SaveKifayah::class)->handle($outreach, $petugas, $kifayah, $outreach->refresh()->edit_version);
        }

        foreach (AttachmentCategory::cases() as $category) {
            if ($category->isBaseline()) {
                app(AttachFileToOutreach::class)->handle($outreach->refresh(), $petugas, $this->image($draftCode, $category), $category);
            }
        }

        if ($outreach->source->usesMustahikKifayah()) {
            app(SignOutreachForm::class)->handle($outreach, $petugas, 'mustahik', $this->scribble(), $outreach->refresh()->edit_version);
            app(SignOutreachForm::class)->handle($outreach, $petugas, 'kifayah', $this->scribble(), $outreach->refresh()->edit_version);
        }

        $missing = OutreachFormSchema::missingForFinalization($outreach->refresh());

        if ($missing !== []) {
            throw new RuntimeException("{$draftCode} is incomplete: ".json_encode($missing));
        }

        app(FinalizeOutreach::class)->handle($outreach, $petugas, $outreach->refresh()->edit_version);

        return $outreach->refresh();
    }

    /**
     * A synthetic attachment image labelled DEMO (dummy-data.md).
     */
    private function image(string $draftCode, AttachmentCategory $category): UploadedFile
    {
        $image = imagecreatetruecolor(800, 500);
        $background = imagecolorallocate($image, 220, 230, 240);
        $ink = imagecolorallocate($image, 30, 30, 30);

        if ($background === false || $ink === false) {
            throw new RuntimeException('Could not draw the demo attachment.');
        }

        imagefill($image, 0, 0, $background);
        imagestring($image, 5, 250, 230, "{$category->label()} - DEMO (SINTETIS)", $ink);
        $path = (string) tempnam(sys_get_temp_dir(), 'demo');
        imagejpeg($image, $path);

        return new UploadedFile($path, strtolower("{$draftCode}-{$category->value}-dummy.jpg"), 'image/jpeg', null, true);
    }

    /**
     * A synthetic signature as the signature pad sends it.
     */
    private function scribble(): string
    {
        $image = imagecreatetruecolor(600, 200);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        $ink = imagecolorallocate($image, 17, 24, 39);

        if ($transparent === false || $ink === false) {
            throw new RuntimeException('Could not draw the demo signature.');
        }

        imagesavealpha($image, true);
        imagefill($image, 0, 0, $transparent);
        imagesetthickness($image, 4);
        imageline($image, 40, 150, 200, 50, $ink);
        imageline($image, 200, 50, 320, 140, $ink);
        imageline($image, 320, 140, 560, 60, $ink);
        ob_start();
        imagepng($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    /**
     * @param  array<string, string>  $client
     */
    private function draft(User $petugas, string $draftCode, string $source, string $type, array $client): Outreach
    {
        if ($existing = Outreach::query()->where('draft_code', $draftCode)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($petugas, $draftCode, $source, $type, $client) {
            $outreach = new Outreach;
            $outreach->forceFill([
                'draft_code' => $draftCode,
                'source_id' => OutreachSource::query()->where('code', $source)->value('id'),
                'type_id' => OutreachType::query()->where('code', $type)->value('id'),
                'outreach_date' => now()->toDateString(),
                'petugas_id' => $petugas->id,
                'status' => OutreachStatus::Draft,
                ...$client,
            ])->save();

            DB::table('history_events')->insert([
                'outreach_id' => $outreach->id,
                'actor_id' => $petugas->id,
                'actor_name_snapshot' => $petugas->name,
                'actor_role_snapshot' => $petugas->role->value,
                'event_type' => 'CREATED',
                'description' => 'Data demo dibuat oleh seeder.',
                'created_at' => now(),
            ]);

            return $outreach;
        });
    }
}
