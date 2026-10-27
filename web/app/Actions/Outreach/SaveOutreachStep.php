<?php

namespace App\Actions\Outreach;

use App\Enums\HistoryEventType;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\User;
use App\Support\OutreachFormSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves the fields of one stepper step as Draft data (Workflow v2.1 sections
 * 8.3 and 10.1) with optimistic concurrency on edit_version (section 12). Does
 * not create a historical version.
 */
class SaveOutreachStep
{
    /**
     * @param  array<string, mixed>  $data  validated fields of the step
     */
    public function handle(Outreach $outreach, User $actor, string $step, array $data, int $expectedVersion): void
    {
        $data = $this->normalize($data);

        DB::transaction(function () use ($outreach, $actor, $step, $data, $expectedVersion) {
            $updated = Outreach::query()
                ->whereKey($outreach->id)
                ->where('edit_version', $expectedVersion)
                ->update([
                    ...$data,
                    'edit_version' => DB::raw('edit_version + 1'),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                throw ValidationException::withMessages([
                    'edit_version' => 'Data Outreach telah berubah di tab atau sesi lain. Muat ulang halaman, lalu ulangi perubahan Anda.',
                ]);
            }

            HistoryEvent::record($outreach, $actor, HistoryEventType::SectionSaved, [
                'metadata' => ['step' => $step],
            ]);
        });
    }

    /**
     * Applies derived values and clears values that no longer apply.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if (array_key_exists('desil_1_10', $data)) {
            // Label follows the chosen desil so the two can never disagree (D-019).
            $data['status_kesejahteraan'] = $data['desil_1_10'] === null
                ? null
                : OutreachFormSchema::DESIL_LABELS[(int) $data['desil_1_10']];
        }

        // Detail fields are "not applicable" when their controlling answer is No (D-027).
        if (($data['bantuan_pernah_diterima'] ?? null) === false) {
            $data['bantuan_detail'] = null;
        }

        if (($data['punya_hutang'] ?? null) === false) {
            $data['besar_hutang'] = null;
        }

        return $data;
    }
}
