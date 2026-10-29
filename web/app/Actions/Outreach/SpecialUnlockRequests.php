<?php

namespace App\Actions\Outreach;

use App\Enums\SpecialUnlockRequestStatus;
use App\Models\Outreach;
use App\Models\SpecialUnlockRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lifecycle of Special Unlock requests (Workflow v2.1 31.4, Database v1.1
 * 13.2): PENDING → CANCELLED by the requester, or PENDING → PROCESSING →
 * REJECTED by the Admin. COMPLETED follows the Admin's Lock or Lock & Ajukan
 * (roadmap R8.2, D-055). Every transition locks its row and checks the state
 * it expects, so double submits and races fail with a clear message.
 */
class SpecialUnlockRequests
{
    public function create(Outreach $outreach, User $requester, string $reason): SpecialUnlockRequest
    {
        return DB::transaction(function () use ($outreach, $requester, $reason) {
            // Locking the Outreach serializes requests for it.
            $locked = Outreach::query()->whereKey($outreach->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->allowsSpecialUnlock()) {
                throw ValidationException::withMessages(['special_unlock' => 'Special Unlock hanya untuk Outreach yang menunggu pengesahan atau sudah selesai.']);
            }

            // One open request per Outreach (D-055).
            if (SpecialUnlockRequest::query()->where('outreach_id', $locked->id)->whereIn('status', SpecialUnlockRequestStatus::open())->exists()) {
                throw ValidationException::withMessages(['special_unlock' => 'Sudah ada permintaan Special Unlock yang masih terbuka untuk Outreach ini.']);
            }

            $request = new SpecialUnlockRequest;
            $request->forceFill([
                'outreach_id' => $locked->id,
                'requester_id' => $requester->id,
                'requester_role_snapshot' => $requester->role,
                'reason' => $reason,
                'status' => SpecialUnlockRequestStatus::Pending,
            ])->save();

            return $request;
        });
    }

    /**
     * Only while nobody has started processing (31.4).
     */
    public function cancel(SpecialUnlockRequest $request): SpecialUnlockRequest
    {
        return $this->transition($request, SpecialUnlockRequestStatus::Pending, fn (SpecialUnlockRequest $locked) => $locked->forceFill([
            'status' => SpecialUnlockRequestStatus::Cancelled,
        ]), 'Permintaan tidak dapat dibatalkan karena sudah diproses Admin.');
    }

    public function startProcessing(SpecialUnlockRequest $request, User $admin): SpecialUnlockRequest
    {
        return $this->transition($request, SpecialUnlockRequestStatus::Pending, fn (SpecialUnlockRequest $locked) => $locked->forceFill([
            'status' => SpecialUnlockRequestStatus::Processing,
            'admin_id' => $admin->id,
        ]), 'Permintaan ini sudah diproses, dibatalkan, atau diselesaikan.');
    }

    /**
     * The reason is shown to the requester (35.5); the Outreach is untouched.
     */
    public function reject(SpecialUnlockRequest $request, User $admin, string $reason): SpecialUnlockRequest
    {
        return $this->transition($request, SpecialUnlockRequestStatus::Processing, fn (SpecialUnlockRequest $locked) => $locked->forceFill([
            'status' => SpecialUnlockRequestStatus::Rejected,
            'admin_id' => $admin->id,
            'processed_reason' => $reason,
            'processed_at' => now(),
        ]), 'Hanya permintaan yang sedang diproses yang dapat ditolak. Muat ulang halaman.');
    }

    /**
     * @param  callable(SpecialUnlockRequest): SpecialUnlockRequest  $apply
     */
    private function transition(SpecialUnlockRequest $request, SpecialUnlockRequestStatus $expected, callable $apply, string $message): SpecialUnlockRequest
    {
        return DB::transaction(function () use ($request, $expected, $apply, $message) {
            $locked = SpecialUnlockRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== $expected) {
                throw ValidationException::withMessages(['special_unlock' => $message]);
            }

            $apply($locked)->save();

            return $locked;
        });
    }
}
