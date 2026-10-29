<?php

namespace App\Actions\MasterData;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\OutreachApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Account management by the ADMIN (Database v1.1 4.1, D-014, D-041, D-060):
 * accounts are never deleted, only made INACTIVE; the role is fixed at
 * creation; at most one KEPALA is active; the NIP belongs to KEPALA only and,
 * once printed on an approval, changes only as an administrative correction
 * with a reason. Everything is audited without passwords.
 */
class ManageUsers
{
    /**
     * @param  array{name: string, email: string, role: UserRole, password: string, nip: string|null}  $data
     */
    public function create(User $admin, array $data): User
    {
        return DB::transaction(function () use ($admin, $data) {
            if ($data['role'] === UserRole::Kepala) {
                $this->ensureNoOtherActiveKepala(null);
            }

            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => $data['password'],
                'role' => $data['role'],
                'status' => UserStatus::Active,
                'nip' => $data['role'] === UserRole::Kepala ? $data['nip'] : null,
            ])->save();

            AuditLog::record($admin, 'USER_CREATED', $user, ['after_data' => $this->values($user)]);

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string, status: UserStatus, nip: string|null, nip_correction_reason: string|null}  $data
     */
    public function update(User $admin, User $user, array $data): void
    {
        DB::transaction(function () use ($admin, $user, $data) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $before = $this->values($locked);
            $nip = $locked->role === UserRole::Kepala ? $data['nip'] : null;

            if ($locked->id === $admin->id && $data['status'] !== UserStatus::Active) {
                throw ValidationException::withMessages(['status' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
            }

            if ($locked->status === UserStatus::Active && $data['status'] === UserStatus::Inactive && $locked->role === UserRole::Admin
                && User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Active)->lockForUpdate()->count() <= 1) {
                throw ValidationException::withMessages(['status' => 'Minimal satu Admin harus tetap aktif.']);
            }

            if ($locked->role === UserRole::Kepala && $data['status'] === UserStatus::Active && $locked->status !== UserStatus::Active) {
                $this->ensureNoOtherActiveKepala($locked->id);
            }

            // A NIP already printed on approvals changes only as a correction (D-041).
            $nipChanged = $locked->role === UserRole::Kepala && $nip !== $locked->nip;
            $nipUsed = $nipChanged && OutreachApproval::query()->where('kepala_id', $locked->id)->where('decision', OutreachApproval::APPROVED)->exists();

            if ($nipUsed && blank($data['nip_correction_reason'])) {
                throw ValidationException::withMessages([
                    'nip_correction_reason' => 'NIP ini sudah dipakai pada pengesahan. Perubahan hanya sebagai koreksi administratif dengan alasan.',
                ]);
            }

            $locked->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => $data['status'],
                'nip' => $nip,
            ])->save();

            if ($locked->wasChanged()) {
                AuditLog::record($admin, $nipUsed ? 'USER_NIP_CORRECTED' : 'USER_UPDATED', $locked, [
                    'before_data' => $before,
                    'after_data' => $this->values($locked),
                    'metadata' => $nipUsed ? ['reason' => $data['nip_correction_reason']] : null,
                ]);
            }
        });
    }

    /**
     * The user changes it again from their own settings (D-044).
     */
    public function resetPassword(User $admin, User $user, string $password): void
    {
        DB::transaction(function () use ($admin, $user, $password) {
            $user->forceFill(['password_hash' => $password])->save();

            AuditLog::record($admin, 'USER_PASSWORD_RESET', $user);
        });
    }

    /**
     * One active Kepala at a time; the Admin deactivates the old one first (D-060).
     */
    private function ensureNoOtherActiveKepala(?int $exceptId): void
    {
        $active = User::query()
            ->where('role', UserRole::Kepala)
            ->where('status', UserStatus::Active)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->lockForUpdate()
            ->first();

        if ($active !== null) {
            throw ValidationException::withMessages([
                'status' => "Masih ada Kepala aktif ({$active->name}). Nonaktifkan akun tersebut terlebih dahulu.",
            ]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function values(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'nip' => $user->nip,
        ];
    }
}
