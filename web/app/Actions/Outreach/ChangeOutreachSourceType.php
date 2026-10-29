<?php

namespace App\Actions\Outreach;

use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\User;
use App\Support\FormSignatures;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Changes Source and Type of a Draft (Workflow v2.1 sections 7.3, 9.5, 9.7).
 * Leaving Baznas/CSR for Bangga Surabaya is destructive: the Mustahik and
 * Kifayah forms and their signatures are cleared, so it needs confirmation.
 * Every other change keeps the data already entered.
 */
class ChangeOutreachSourceType
{
    /**
     * Whether moving to the given Source clears the Mustahik and Kifayah forms.
     */
    public static function isDestructive(OutreachSource $from, OutreachSource $to): bool
    {
        return $from->usesMustahikKifayah() && ! $to->usesMustahikKifayah();
    }

    public function handle(Outreach $outreach, User $actor, OutreachSource $source, OutreachType $type, int $expectedVersion, bool $confirmed): void
    {
        $outreach->loadMissing(['source', 'type']);
        $destructive = self::isDestructive($outreach->source, $source);

        if ($destructive && ! $confirmed) {
            throw ValidationException::withMessages([
                'confirm_destructive' => 'Konfirmasi diperlukan: data Mustahik, Kifayah, dan tanda tangannya akan dihapus.',
            ]);
        }

        $changes = array_filter([
            'source' => $outreach->source_id === $source->id ? null : ['before' => $outreach->source->name, 'after' => $source->name],
            'type' => $outreach->type_id === $type->id ? null : ['before' => $outreach->type->name, 'after' => $type->name],
        ]);

        if ($changes === []) {
            return;
        }

        $purgedFiles = DB::transaction(function () use ($outreach, $actor, $source, $type, $expectedVersion, $destructive, $changes) {
            $updated = Outreach::query()
                ->whereKey($outreach->id)
                ->where('edit_version', $expectedVersion)
                ->update([
                    'source_id' => $source->id,
                    'type_id' => $type->id,
                    'edit_version' => DB::raw('edit_version + 1'),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                throw ValidationException::withMessages([
                    'edit_version' => 'Data Outreach telah berubah di tab atau sesi lain. Muat ulang halaman, lalu ulangi perubahan Anda.',
                ]);
            }

            $files = $destructive ? $this->clearSourceForms($outreach) : [];

            HistoryEvent::recordEdit($outreach, $actor, [
                'description' => $destructive ? 'Source diubah; form Mustahik dan Kifayah beserta tanda tangannya dihapus.' : 'Source/Jenis diubah.',
                'field_changes' => $changes,
                'metadata' => ['step' => 'identitas', 'cleared_forms' => $destructive],
            ]);

            return $files;
        });

        // Stored signature images are removed only after the commit succeeded.
        foreach ($purgedFiles as $file) {
            Storage::disk($file['disk'])->delete($file['key']);
        }
    }

    /**
     * Removes the current Mustahik and Kifayah data and invalidates their
     * signatures. Signatures a historical version refers to (a Source change
     * through Special Unlock) stay as submitted.
     *
     * @return array<int, array{disk: string, key: string}> stored files to remove
     */
    private function clearSourceForms(Outreach $outreach): array
    {
        $signatureIds = [];

        foreach (['mustahik' => 'mustahik_form_id', 'kifayah' => 'kifayah_form_id'] as $form => $foreignKey) {
            $rows = DB::table("{$form}_forms")->where('outreach_id', $outreach->id)->get(['id', 'signature_id']);

            DB::table("{$form}_answers")->whereIn($foreignKey, $rows->pluck('id'))->delete();
            DB::table("{$form}_forms")->whereIn('id', $rows->pluck('id'))->delete();
            $signatureIds = [...$signatureIds, ...$rows->pluck('signature_id')->filter()->all()];
        }

        $signatureIds = array_values(array_filter($signatureIds, fn (int $id) => ! FormSignatures::isInVersion($id)));

        if ($signatureIds === []) {
            return [];
        }

        $fileIds = DB::table('signatures')->whereIn('id', $signatureIds)->pluck('file_id');
        $files = DB::table('files')->whereIn('id', $fileIds)->get(['storage_disk', 'storage_key'])
            ->map(fn (object $file) => ['disk' => (string) $file->storage_disk, 'key' => (string) $file->storage_key])
            ->values()
            ->all();

        DB::table('signatures')->whereIn('id', $signatureIds)->delete();
        DB::table('files')->whereIn('id', $fileIds)->update(['purged_at' => now(), 'updated_at' => now()]);

        return $files;
    }
}
