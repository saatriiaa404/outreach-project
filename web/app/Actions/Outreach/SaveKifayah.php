<?php

namespace App\Actions\Outreach;

use App\Enums\UserRole;
use App\Models\HistoryEvent;
use App\Models\KifayahAnswer;
use App\Models\KifayahForm;
use App\Models\Outreach;
use App\Models\User;
use App\Support\FormSignatures;
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
            $changed = $form->wasChanged() && array_keys($form->getChanges()) !== ['updated_at'];

            foreach (array_keys(KifayahFormSchema::QUESTIONS) as $code) {
                $option = $data[$code] ?? null;

                if ($option === null) {
                    $changed = KifayahAnswer::query()->where('kifayah_form_id', $form->id)->where('question_code', $code)->delete() > 0 || $changed;

                    continue;
                }

                $answer = KifayahAnswer::query()->updateOrCreate(
                    ['kifayah_form_id' => $form->id, 'question_code' => $code],
                    ['answer' => ['option' => (string) $option]],
                );
                $changed = $answer->wasRecentlyCreated || $answer->wasChanged() || $changed;
            }

            // The signer no longer signed this data (Workflow v2.1 16.4).
            if ($changed) {
                FormSignatures::invalidate($outreach, 'kifayah');
            }

            // Admin changes during Special Unlock are always recorded (25.8).
            if (! $autosave || ($actor->role === UserRole::Admin && $changed)) {
                HistoryEvent::recordEdit($outreach, $actor, [
                    'metadata' => ['step' => 'kifayah'],
                ]);
            }
        });

        return $expectedVersion + 1;
    }
}
