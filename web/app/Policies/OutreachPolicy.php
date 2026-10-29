<?php

namespace App\Policies;

use App\Enums\OutreachStatus;
use App\Enums\UserRole;
use App\Models\Outreach;
use App\Models\User;
use App\Support\SpecialUnlock;

/**
 * Server-side Outreach authorization following roles-permissions.md and
 * Workflow v2.1: role, then ownership/scope, then status, then action.
 * Validation, version checks, and business gates are enforced by the actions
 * themselves; a policy only answers whether the user may attempt them.
 */
class OutreachPolicy
{
    /**
     * Inactive accounts may not do anything, even with a stale session.
     */
    public function before(User $user): ?bool
    {
        return $user->isActive() ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Full detail access (form data, attachments, versions).
     */
    public function view(User $user, Outreach $outreach): bool
    {
        return match ($user->role) {
            UserRole::Petugas => $this->owns($user, $outreach),
            UserRole::Kepala => in_array($outreach->status, [OutreachStatus::MenungguPengesahan, OutreachStatus::Selesai], true),
            UserRole::Admin => true,
        };
    }

    /**
     * Status and Return note only; KEPALA sees PERLU_PERBAIKAN this way (D-039).
     */
    public function viewSummary(User $user, Outreach $outreach): bool
    {
        return $this->view($user, $outreach)
            || ($user->role === UserRole::Kepala && $outreach->status === OutreachStatus::PerluPerbaikan);
    }

    /**
     * ADMIN does not create Outreach from scratch (D-016).
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Petugas;
    }

    /**
     * Data editing: the owning Petugas in the normal flow, or the ADMIN
     * while Special Unlock is open (Workflow v2.1 25.4, D-016).
     */
    public function update(User $user, Outreach $outreach): bool
    {
        return $this->editsAsPetugas($user, $outreach) || $this->editsAsAdmin($user, $outreach);
    }

    /**
     * Source and Type change by normal editing only before the first
     * Finalisasi; afterwards only through Special Unlock (Workflow v2.1 7.3, 9.6).
     */
    public function changeSourceType(User $user, Outreach $outreach): bool
    {
        return ($this->editsAsPetugas($user, $outreach) && $outreach->status === OutreachStatus::Draft)
            || $this->editsAsAdmin($user, $outreach);
    }

    /**
     * Signatures come from the Petugas and the Warga on the Petugas's device,
     * never from the ADMIN (Workflow v2.1 16).
     */
    public function sign(User $user, Outreach $outreach): bool
    {
        return $this->editsAsPetugas($user, $outreach);
    }

    public function finalize(User $user, Outreach $outreach): bool
    {
        return $this->editsAsPetugas($user, $outreach);
    }

    /**
     * Lock and Lock & Ajukan close the ADMIN's Special Unlock session.
     */
    public function lockSpecialUnlock(User $user, Outreach $outreach): bool
    {
        return $this->editsAsAdmin($user, $outreach);
    }

    public function return(User $user, Outreach $outreach): bool
    {
        return $this->decides($user, $outreach);
    }

    public function approve(User $user, Outreach $outreach): bool
    {
        return $this->decides($user, $outreach);
    }

    public function requestSpecialUnlock(User $user, Outreach $outreach): bool
    {
        if (! $outreach->status->allowsSpecialUnlock()) {
            return false;
        }

        return match ($user->role) {
            UserRole::Petugas => $this->owns($user, $outreach),
            UserRole::Kepala => true,
            UserRole::Admin => false,
        };
    }

    public function specialUnlock(User $user, Outreach $outreach): bool
    {
        return $user->role === UserRole::Admin
            && $outreach->status->allowsSpecialUnlock()
            && ! SpecialUnlock::isActive($outreach);
    }

    /**
     * Final PDFs exist only for approved Outreach; PETUGAS never downloads
     * them (D-040).
     */
    public function downloadFinalPdf(User $user, Outreach $outreach): bool
    {
        return in_array($user->role, [UserRole::Kepala, UserRole::Admin], true)
            && $outreach->status === OutreachStatus::Selesai;
    }

    private function editsAsPetugas(User $user, Outreach $outreach): bool
    {
        return $user->role === UserRole::Petugas
            && $this->owns($user, $outreach)
            && $outreach->status->isEditableByPetugas();
    }

    private function editsAsAdmin(User $user, Outreach $outreach): bool
    {
        return $user->role === UserRole::Admin
            && $outreach->status->allowsSpecialUnlock()
            && SpecialUnlock::isActive($outreach);
    }

    private function owns(User $user, Outreach $outreach): bool
    {
        return $outreach->petugas_id === $user->id;
    }

    private function decides(User $user, Outreach $outreach): bool
    {
        return $user->role === UserRole::Kepala
            && $outreach->status === OutreachStatus::MenungguPengesahan;
    }
}
