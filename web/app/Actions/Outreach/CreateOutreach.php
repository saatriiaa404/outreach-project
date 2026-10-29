<?php

namespace App\Actions\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Create Outreach flow (Workflow v2.1 section 7): Source, Type, and Date are
 * chosen first; the record starts as DRAFT with an internal draft_code and no
 * outreach_number until the first Finalisasi.
 */
class CreateOutreach
{
    public function handle(User $petugas, int $sourceId, int $typeId, string $outreachDate): Outreach
    {
        return DB::transaction(function () use ($petugas, $sourceId, $typeId, $outreachDate) {
            $outreach = new Outreach;
            $outreach->forceFill([
                'draft_code' => $this->newDraftCode(),
                'source_id' => $sourceId,
                'type_id' => $typeId,
                'outreach_date' => $outreachDate,
                'petugas_id' => $petugas->id,
                'status' => OutreachStatus::Draft,
            ])->save();

            HistoryEvent::record($outreach, $petugas, HistoryEventType::Created);

            return $outreach;
        });
    }

    /**
     * DRAFT- followed by 12 random characters (D-043, Q-04).
     */
    private function newDraftCode(): string
    {
        do {
            $code = 'DRAFT-'.Str::upper(Str::random(12));
        } while (Outreach::query()->where('draft_code', $code)->exists());

        return $code;
    }
}
