<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Manual weekly backup by the Admin (D-038): the MySQL database and every
 * private file (attachments, signatures, templates), with a manifest of
 * hashes so a restore can prove the copy is complete before touching data.
 */
class Backup
{
    public const MANIFEST = 'manifest.json';

    public const DATABASE = 'database.sql';

    public const FILES = 'files';

    public function __construct(private Filesystem $files) {}

    /**
     * Root of the private disk that holds every stored file.
     */
    public function filesRoot(): string
    {
        return rtrim(Storage::disk('local')->path(''), '\\/');
    }

    /**
     * Connection settings for the mysql and mysqldump clients; the password
     * goes through MYSQL_PWD so it never appears in the process list.
     *
     * @return array{args: list<string>, env: array<string, string>, database: string}
     */
    public function client(): array
    {
        $config = DB::connection()->getConfig();

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Backup hanya mendukung database MySQL.');
        }

        return [
            'args' => [
                '--host='.$config['host'],
                '--port='.$config['port'],
                '--user='.$config['username'],
                '--default-character-set=utf8mb4',
            ],
            'env' => ['MYSQL_PWD' => (string) ($config['password'] ?? '')],
            'database' => (string) $config['database'],
        ];
    }

    /**
     * Row count per table, used to check a restore.
     *
     * @return array<string, int>
     */
    public function rowCounts(): array
    {
        $counts = [];

        foreach (Schema::getTableListing(DB::connection()->getDatabaseName(), false) as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        ksort($counts);

        return $counts;
    }

    /**
     * SHA-256 and size of every file under a folder, keyed by relative path.
     *
     * @return array<string, array{sha256: string, size: int}>
     */
    public function hashFolder(string $root): array
    {
        $list = [];

        if (! $this->files->isDirectory($root)) {
            return $list;
        }

        foreach ($this->files->allFiles($root, true) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $list[$relative] = ['sha256' => (string) hash_file('sha256', $file->getPathname()), 'size' => $file->getSize()];
        }

        ksort($list);

        return $list;
    }

    /**
     * Problems found when checking a backup folder against its manifest.
     *
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    public function verify(string $folder, array $manifest): array
    {
        $problems = [];
        $sql = $folder.DIRECTORY_SEPARATOR.self::DATABASE;

        if (! is_file($sql) || hash_file('sha256', $sql) !== ($manifest['database']['sha256'] ?? null)) {
            $problems[] = self::DATABASE.' hilang atau isinya berbeda dari manifest.';
        }

        $actual = $this->hashFolder($folder.DIRECTORY_SEPARATOR.self::FILES);

        foreach ($manifest['files'] ?? [] as $path => $expected) {
            if (($actual[$path]['sha256'] ?? null) !== $expected['sha256']) {
                $problems[] = "Berkas {$path} hilang atau isinya berbeda.";
            }
        }

        foreach (array_diff_key($actual, $manifest['files'] ?? []) as $path => $unused) {
            $problems[] = "Berkas {$path} tidak tercatat di manifest.";
        }

        return $problems;
    }
}
