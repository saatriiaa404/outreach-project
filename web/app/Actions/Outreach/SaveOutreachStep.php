<?php

namespace App\Actions\Outreach;

use App\Enums\UserRole;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\User;
use App\Support\FormSignatures;
use App\Support\OutreachFormSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves the fields of one stepper step as Draft data (Workflow v2.1 sections
 * 8.3 and 10.1) with optimistic concurrency on edit_version (section 12). Does
 * not create a historical version. Autosave uses the same path but is not
 * recorded in History (section 10.2).
 */
class SaveOutreachStep
{
    /**
     * @param  array<string, mixed>  $data  validated fields of the step
     * @return int the new edit_version
     */
    public function handle(Outreach $outreach, User $actor, string $step, array $data, int $expectedVersion, bool $autosave = false): int
    {
        $data = $this->normalize($data);

        DB::transaction(function () use ($outreach, $actor, $step, $data, $expectedVersion, $autosave) {
            $before = (array) DB::table('outreaches')->where('id', $outreach->id)->first(array_keys($data));

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

            // Data printed on a signed form changed, so that signature no longer applies (16.4).
            foreach (FormSignatures::OUTREACH_FIELDS as $form => $fields) {
                $changed = collect($fields)->contains(fn (string $field) => array_key_exists($field, $data)
                    && (string) ($before[$field] ?? '') !== (string) ($data[$field] ?? ''));

                if ($changed) {
                    FormSignatures::invalidate($outreach, $form);
                }
            }

            if ($actor->role === UserRole::Admin) {
                // Admin changes during Special Unlock are always recorded with their values (25.8).
                $changes = [];

                foreach ($data as $field => $value) {
                    if ((string) ($before[$field] ?? '') !== (string) ($value ?? '')) {
                        $changes[$field] = ['from' => $before[$field] ?? null, 'to' => $value];
                    }
                }

                if ($changes !== [] || ! $autosave) {
                    HistoryEvent::recordEdit($outreach, $actor, ['field_changes' => $changes, 'metadata' => ['step' => $step]]);
                }
            } elseif (! $autosave) {
                HistoryEvent::recordEdit($outreach, $actor, [
                    'metadata' => ['step' => $step],
                ]);
            }
        });

        return $expectedVersion + 1;
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
