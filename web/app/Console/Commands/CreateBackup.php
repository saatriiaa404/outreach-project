<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;

/**
 * Weekly manual backup by the Admin to separate media (D-038). Writes a new
 * folder with database.sql, files/ and manifest.json, then checks the copy.
 */
#[Signature('backup:create {destination : Folder tujuan, mis. drive eksternal E:\\backup-outreach}')]
#[Description('Backup database dan seluruh berkas privat ke folder tujuan')]
class CreateBackup extends Command
{
    public function handle(Backup $backup, Filesystem $files): int
    {
        $destination = rtrim((string) $this->argument('destination'), '\\/');

        if (! $files->isDirectory($destination) || ! is_writable($destination)) {
            $this->error("Folder tujuan {$destination} tidak ada atau tidak dapat ditulis.");

            return self::FAILURE;
        }

        $folder = $destination.DIRECTORY_SEPARATOR.'outreach-backup-'.now()->format('Ymd-His');
        $files->makeDirectory($folder);
        $client = $backup->client();
        $sql = $folder.DIRECTORY_SEPARATOR.Backup::DATABASE;

        // A consistent InnoDB snapshot without locking the application.
        $dump = Process::env($client['env'])->timeout(600)->run([
            'mysqldump', ...$client['args'],
            '--single-transaction', '--no-tablespaces', '--add-drop-table',
            '--result-file='.$sql, $client['database'],
        ]);

        if ($dump->failed() || ! is_file($sql)) {
            $this->error('Ekspor database gagal: '.trim($dump->errorOutput()));
            $files->deleteDirectory($folder);

            return self::FAILURE;
        }

        $root = $backup->filesRoot();
        $files->copyDirectory($root, $folder.DIRECTORY_SEPARATOR.Backup::FILES);
        $manifest = [
            'created_at' => now()->toIso8601String(),
            'database' => [
                'name' => $client['database'],
                'sha256' => hash_file('sha256', $sql),
                'rows' => $backup->rowCounts(),
            ],
            'files' => $backup->hashFolder($root),
        ];
        $files->put($folder.DIRECTORY_SEPARATOR.Backup::MANIFEST, (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $problems = $backup->verify($folder, $manifest);

        if ($problems !== []) {
            $this->error('Salinan tidak cocok dengan sumbernya; backup ini jangan dipakai.');
            $this->line(implode(PHP_EOL, $problems));

            return self::FAILURE;
        }

        $this->info("Backup selesai: {$folder}");
        $this->line(count($manifest['database']['rows']).' tabel, '.array_sum($manifest['database']['rows']).' baris, '.count($manifest['files']).' berkas.');

        return self::SUCCESS;
    }
}
