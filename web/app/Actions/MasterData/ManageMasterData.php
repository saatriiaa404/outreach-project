<?php

namespace App\Actions\MasterData;

use App\Models\AuditLog;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Master Source and Type by the ADMIN (D-040, D-057): the code never changes
 * after creation, nothing is deleted, and active controls use in new
 * Outreach. Existing Outreach and historical versions keep their Source and
 * Type; versions also keep the name in their snapshot. Every change is
 * audited.
 */
class ManageMasterData
{
    public function createType(User $admin, string $code, string $name): OutreachType
    {
        return DB::transaction(function () use ($admin, $code, $name) {
            $type = new OutreachType;
            $type->forceFill(['code' => $code, 'name' => $name, 'active' => true])->save();

            AuditLog::record($admin, 'MASTER_TYPE_CREATED', $type, [
                'after_data' => ['code' => $code, 'name' => $name, 'active' => true],
            ]);

            return $type;
        });
    }

    public function update(User $admin, OutreachSource|OutreachType $item, string $name, bool $active): void
    {
        DB::transaction(function () use ($admin, $item, $name, $active) {
            $locked = $item::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $before = ['name' => $locked->name, 'active' => $locked->active];

            // New Outreach always needs at least one Source and one Type to choose.
            if ($locked->active && ! $active && $item::query()->where('active', true)->lockForUpdate()->count() <= 1) {
                throw ValidationException::withMessages([
                    'active' => 'Minimal satu '.($item instanceof OutreachSource ? 'Source' : 'Jenis Outreach').' harus tetap aktif.',
                ]);
            }

            $locked->forceFill(['name' => $name, 'active' => $active])->save();

            if ($locked->wasChanged()) {
                AuditLog::record($admin, $item instanceof OutreachSource ? 'MASTER_SOURCE_UPDATED' : 'MASTER_TYPE_UPDATED', $locked, [
                    'before_data' => $before,
                    'after_data' => ['name' => $name, 'active' => $active],
                    'metadata' => ['code' => $locked->code],
                ]);
            }
        });
    }
}
