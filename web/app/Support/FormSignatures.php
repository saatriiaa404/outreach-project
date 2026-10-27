<?php

namespace App\Support;

use App\Enums\SignerType;
use App\Models\KifayahForm;
use App\Models\MustahikForm;
use App\Models\Outreach;
use App\Models\Signature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The two pre-approval signatures of Baznas/CSR (Workflow v2.1 section 16.1):
 * the Petugas signs the Mustahik form and the Warga signs the Kifayah form.
 * Before Finalisasi a signature can be cleared and redone (16.4); it is also
 * cleared when data shown on the signed form changes.
 */
class FormSignatures
{
    /**
     * @var array<string, array{signer: SignerType, title: string, document: string}>
     */
    public const FORMS = [
        'mustahik' => ['signer' => SignerType::Petugas, 'title' => 'Tanda tangan Petugas', 'document' => 'Form Mustahik'],
        'kifayah' => ['signer' => SignerType::Warga, 'title' => 'Tanda tangan Warga', 'document' => 'Form Had Kifayah'],
    ];

    /**
     * Outreach fields printed on each signed form (D-036): changing them
     * changes what the signer signed.
     *
     * @var array<string, list<string>>
     */
    public const OUTREACH_FIELDS = [
        'mustahik' => ['no_telp'],
        'kifayah' => ['nama', 'nik', 'alamat_domisili', 'no_telp'],
    ];

    public static function form(Outreach $outreach, string $form): MustahikForm|KifayahForm|null
    {
        return $form === 'mustahik'
            ? MustahikForm::query()->where('outreach_id', $outreach->id)->first()
            : KifayahForm::query()->where('outreach_id', $outreach->id)->first();
    }

    /**
     * Labels of the signatures Finalisasi still needs.
     *
     * @return list<string>
     */
    public static function missing(Outreach $outreach): array
    {
        $missing = [];

        foreach (self::FORMS as $form => $definition) {
            if (self::form($outreach, $form)?->signature_id === null) {
                $missing[] = $definition['title'];
            }
        }

        return $missing;
    }

    /**
     * Clears the signature of a form, if any. Must run inside the caller's
     * transaction; the stored image is removed after the commit.
     */
    public static function invalidate(Outreach $outreach, string $form): bool
    {
        $record = self::form($outreach, $form);

        if ($record?->signature_id === null) {
            return false;
        }

        $signatureId = $record->signature_id;
        $record->forceFill(['signature_id' => null])->save();
        self::discard($signatureId);

        return true;
    }

    /**
     * Deletes a Draft signature that no historical version refers to yet.
     */
    public static function discard(int $signatureId): void
    {
        $signature = Signature::query()->with('file')->find($signatureId);

        if ($signature === null) {
            return;
        }

        $file = $signature->file;
        $signature->delete();
        $file->forceFill(['purged_at' => now()])->save();

        DB::afterCommit(fn () => Storage::disk($file->storage_disk)->delete($file->storage_key));
    }
}
