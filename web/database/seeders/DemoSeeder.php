<?php

namespace Database\Seeders;

use App\Enums\OutreachStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\SignatureTemplate;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Synthetic demo data from docs/prototype/dummy-data.md. Never contains real
 * residents' data. The demo password comes from DEMO_USER_PASSWORD in the
 * local .env so it is not committed.
 *
 * DEMO-002 (MENUNGGU_PENGESAHAN) and DEMO-004 (SELESAI) need historical
 * versions, approvals, and attachments, which exist only after roadmap R6 and
 * R7; they are not seeded here so that status never contradicts the latest
 * version (database.md section 13.2).
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
        $kepala = $this->user('Budi Kepala', 'kepala.demo@example.test', UserRole::Kepala, $password, '000000000000000001');
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
    }

    private function user(string $name, string $email, UserRole $role, string $password, ?string $nip = null): User
    {
        return User::query()->updateOrCreate(['email' => $email], [
            'name' => $name,
            'password_hash' => $password,
            'nip' => $nip,
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
     * @param  array<string, string>  $client
     */
    private function draft(User $petugas, string $draftCode, string $source, string $type, array $client): void
    {
        if (Outreach::query()->where('draft_code', $draftCode)->exists()) {
            return;
        }

        DB::transaction(function () use ($petugas, $draftCode, $source, $type, $client) {
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
        });
    }
}
