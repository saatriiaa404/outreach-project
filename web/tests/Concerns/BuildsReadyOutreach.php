<?php

namespace Tests\Concerns;

use App\Enums\AttachmentCategory;
use App\Enums\SignerType;
use App\Models\KifayahForm;
use App\Models\MustahikForm;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\OutreachSource;
use App\Models\Signature;
use App\Models\StoredFile;
use App\Support\KifayahFormSchema;
use App\Support\MustahikFormSchema;
use App\Support\OutreachFormSchema;
use Illuminate\Support\Facades\Storage;

/**
 * Builds an Outreach that meets every Finalisasi requirement of its Source.
 * Requires the MasterDataSeeder and a faked local disk.
 */
trait BuildsReadyOutreach
{
    /**
     * A registered file; its bytes are a tiny real image so PDFs can embed it.
     */
    protected function file(string $mime = 'image/jpeg'): StoredFile
    {
        $key = 'test/'.uniqid().'.bin';
        $image = imagecreatetruecolor(8, 8);
        ob_start();
        $mime === 'image/png' ? imagepng($image) : imagejpeg($image);
        Storage::disk('local')->put($key, (string) ob_get_clean());

        $file = new StoredFile;
        $file->forceFill([
            'storage_disk' => 'local', 'storage_key' => $key, 'original_name' => 'demo',
            'mime_type' => $mime, 'extension' => 'jpg', 'size_bytes' => 1, 'sha256_hash' => hash('sha256', uniqid()),
        ])->save();

        return $file;
    }

    protected function signature(SignerType $type, string $name): int
    {
        $signature = new Signature;
        $signature->forceFill([
            'file_id' => $this->file('image/png')->id, 'signer_type' => $type,
            'signer_name_snapshot' => $name, 'signed_at' => now(),
        ])->save();

        return $signature->id;
    }

    /**
     * An Outreach that meets every Finalisasi requirement of its Source.
     */
    protected function ready(string $sourceCode = 'BAZNAS', string $date = '2026-10-27'): Outreach
    {
        $data = [];
        foreach (OutreachFormSchema::steps() as $step => $definition) {
            foreach ($definition['fields'] as $name => $field) {
                if ($step !== 'identitas' && $field['required']) {
                    $data[$name] = match ($field['input']) {
                        'integer', 'money' => 0,
                        'date' => '1980-01-01',
                        'boolean' => false,
                        'select' => array_key_first($field['options']),
                        default => 'Isian demo',
                    };
                }
            }
        }

        $outreach = Outreach::factory()
            ->for(OutreachSource::query()->where('code', $sourceCode)->firstOrFail(), 'source')
            ->create([...$data, 'outreach_date' => $date, 'nama' => 'Warga Demo', 'desil_1_10' => 9, 'status_kesejahteraan' => 'Sejahtera'])
            ->refresh();

        foreach (AttachmentCategory::cases() as $category) {
            if ($category->isBaseline()) {
                (new OutreachAttachment)->forceFill(['outreach_id' => $outreach->id, 'file_id' => $this->file()->id, 'category' => $category])->save();
            }
        }

        if ($outreach->source->usesMustahikKifayah()) {
            $mustahik = new MustahikForm;
            $mustahik->forceFill([
                'outreach_id' => $outreach->id, 'petugas_id' => $outreach->petugas_id, 'survey_date' => $date,
                'kepala_keluarga_nama' => 'Kepala Demo', 'kepala_keluarga_nik_no_kk' => 'DUMMY-KK', 'kepala_keluarga_alamat' => 'Jl. Demo',
                'kepala_keluarga_rt' => '001', 'kepala_keluarga_rw' => '002',
                'signature_id' => $this->signature(SignerType::Petugas, $outreach->petugas->name),
            ])->save();
            foreach (array_keys(MustahikFormSchema::QUESTIONS) as $code) {
                $mustahik->answers()->create(['question_code' => $code, 'answer' => ['option' => '1']]);
            }

            $kifayah = new KifayahForm;
            $kifayah->forceFill([
                'outreach_id' => $outreach->id,
                ...array_map(fn () => 0, KifayahFormSchema::FIELDS),
                'signature_id' => $this->signature(SignerType::Warga, 'Warga Demo'),
            ])->save();
            foreach (array_keys(KifayahFormSchema::QUESTIONS) as $code) {
                $kifayah->answers()->create(['question_code' => $code, 'answer' => ['option' => '2']]);
            }
        }

        return $outreach->refresh();
    }
}
