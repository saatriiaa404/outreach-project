<?php

namespace App\Actions\Outreach;

use App\Enums\HistoryEventType;
use App\Models\HistoryEvent;
use App\Models\MustahikAnswer;
use App\Models\MustahikForm;
use App\Models\Outreach;
use App\Models\User;
use App\Support\FormSignatures;
use App\Support\MustahikFormSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves the Mustahik header and answers as Draft data. Uses the Outreach
 * edit_version like every other save (Workflow v2.1 section 12); autosave is
 * not recorded in History (section 10.2).
 */
class SaveMustahik
{
    /**
     * @param  array<string, string|null>  $data  validated header fields and answers keyed by question code
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

            $form = MustahikForm::query()->firstOrNew(['outreach_id' => $outreach->id]);
            $form->forceFill([
                'petugas_id' => $form->petugas_id ?? $outreach->petugas_id,
                ...array_intersect_key($data, MustahikFormSchema::HEADER),
            ])->save();
            $changed = $form->wasChanged() && array_keys($form->getChanges()) !== ['updated_at'];

            foreach (array_keys(MustahikFormSchema::QUESTIONS) as $code) {
                $option = $data[$code] ?? null;

                if ($option === null) {
                    $changed = MustahikAnswer::query()->where('mustahik_form_id', $form->id)->where('question_code', $code)->delete() > 0 || $changed;

                    continue;
                }

                $answer = MustahikAnswer::query()->updateOrCreate(
                    ['mustahik_form_id' => $form->id, 'question_code' => $code],
                    ['answer' => ['option' => $option]],
                );
                $changed = $answer->wasRecentlyCreated || $answer->wasChanged() || $changed;
            }

            // The signer no longer signed this data (Workflow v2.1 16.4).
            if ($changed) {
                FormSignatures::invalidate($outreach, 'mustahik');
            }

            if (! $autosave) {
                HistoryEvent::record($outreach, $actor, HistoryEventType::SectionSaved, [
                    'metadata' => ['step' => 'mustahik'],
                ]);
            }
        });

        return $expectedVersion + 1;
    }
}
