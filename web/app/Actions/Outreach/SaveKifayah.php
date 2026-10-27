<?php

namespace App\Actions\Outreach;

use App\Enums\HistoryEventType;
use App\Models\HistoryEvent;
use App\Models\KifayahAnswer;
use App\Models\KifayahForm;
use App\Models\Outreach;
use App\Models\User;
use App\Support\KifayahFormSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves the Kifayah fields and answers as Draft data. Uses the Outreach
 * edit_version like every other save (Workflow v2.1 section 12); autosave is
 * not recorded in History (section 10.2).
 */
class SaveKifayah
{
    /**
     * @param  array<string, int|string|null>  $data  validated fields (int or null) and answers keyed by question code
     * @return int the new edit_version
     */
    public function handle(Outreach $outreach, User $actor, array $data, int $expectedVersion, bool $autosave = false): int
    {
        DB::transaction(function () use ($outreach, $actor, $data, $expectedVersion, $autosave) {
            $updated = Outreach::query()
                ->whereKey($outreach->id)
                ->where('edit_version', $expectedVersion)
                ->update(['edit_version' => DB::raw('edit_version + 1'), 'updated_at' => now()]);

            if ($updated === 0) {
                throw ValidationException::withMessages([
                    'edit_version' => 'Data Outreach telah berubah di tab atau sesi lain. Muat ulang halaman, lalu ulangi perubahan Anda.',
                ]);
            }

            $form = KifayahForm::query()->firstOrNew(['outreach_id' => $outreach->id]);
            $form->forceFill(array_intersect_key($data, KifayahFormSchema::FIELDS))->save();

            foreach (array_keys(KifayahFormSchema::QUESTIONS) as $code) {
                $option = $data[$code] ?? null;

                if ($option === null) {
                    KifayahAnswer::query()->where('kifayah_form_id', $form->id)->where('question_code', $code)->delete();

                    continue;
                }

                KifayahAnswer::query()->updateOrCreate(
                    ['kifayah_form_id' => $form->id, 'question_code' => $code],
                    ['answer' => ['option' => (string) $option]],
                );
            }

            if (! $autosave) {
                HistoryEvent::record($outreach, $actor, HistoryEventType::SectionSaved, [
                    'metadata' => ['step' => 'kifayah'],
                ]);
            }
        });

        return $expectedVersion + 1;
    }
}
