<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;

/**
 * Restores a backup made by backup:create (D-038). The backup is checked
 * against its manifest first; the current private files are moved aside, not
 * deleted, so a mistaken restore can still be undone by hand.
 */
#[Signature('backup:restore {folder : Folder outreach-backup-... yang akan dipulihkan} {--force : Lewati konfirmasi}')]
#[Description('Pulihkan database dan berkas privat dari folder backup')]
class RestoreBackup extends Command
{
    public function handle(Backup $backup, Filesystem $files): int
    {
        $folder = rtrim((string) $this->argument('folder'), '\\/');
        $manifestPath = $folder.DIRECTORY_SEPARATOR.Backup::MANIFEST;
        $manifest = is_file($manifestPath) ? json_decode((string) $files->get($manifestPath), true) : null;

        if (! is_array($manifest)) {
            $this->error("{$folder} bukan folder backup (manifest.json tidak ada atau rusak).");

            return self::FAILURE;
        }

        $problems = $backup->verify($folder, $manifest);

        if ($problems !== []) {
            $this->error('Backup tidak utuh; pemulihan dibatalkan tanpa mengubah data.');
            $this->line(implode(PHP_EOL, $problems));

            return self::FAILURE;
        }

        $client = $backup->client();
        $this->line("Backup {$manifest['created_at']}: ".count($manifest['files']).' berkas, database asal '.$manifest['database']['name'].'.');

        if (! $this->option('force') && ! $this->confirm("Seluruh isi database {$client['database']} dan berkas privat akan diganti dengan isi backup ini. Lanjutkan?")) {
            $this->line('Dibatalkan.');

            return self::FAILURE;
        }

        $root = $backup->filesRoot();
        $aside = $root.'-sebelum-restore-'.now()->format('Ymd-His');

        if ($files->isDirectory($root) && ! $files->moveDirectory($root, $aside)) {
            $this->error("Folder berkas {$root} tidak dapat dipindahkan; pemulihan dibatalkan tanpa mengubah data.");

            return self::FAILURE;
        }

        $import = Process::env($client['env'])->timeout(600)
            ->input((string) $files->get($folder.DIRECTORY_SEPARATOR.Backup::DATABASE))
            ->run(['mysql', ...$client['args'], $client['database']]);

        if ($import->failed()) {
            $this->error('Impor database gagal: '.trim($import->errorOutput()));

            if ($files->isDirectory($aside)) {
                $files->moveDirectory($aside, $root);
            }

            return self::FAILURE;
        }

        $files->copyDirectory($folder.DIRECTORY_SEPARATOR.Backup::FILES, $root);
        $mismatch = array_diff_assoc($manifest['database']['rows'], $backup->rowCounts());

        if ($backup->hashFolder($root) != $manifest['files'] || $mismatch !== []) {
            $this->error('Hasil pemulihan tidak sama dengan backup: '.implode(', ', array_keys($mismatch)));

            return self::FAILURE;
        }

        $this->info('Pemulihan selesai dan cocok dengan backup.');
        $this->line("Berkas sebelum pemulihan disimpan di {$aside}; hapus manual setelah aplikasi diperiksa.");

        return self::SUCCESS;
    }
}
