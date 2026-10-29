<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Backup;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * backup:create and backup:restore (D-038). The mysql clients are faked here;
 * the real round trip on the test database is recorded in testing.md (R11.3).
 */
class BackupTest extends TestCase
{
    use RefreshDatabase;

    private string $destination;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put('attachments/1/ktp.jpg', 'gambar ktp');
        Storage::disk('local')->put('signatures/1.png', 'tanda tangan');
        User::factory()->count(2)->create();

        $this->destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.'outreach-backup-test-'.uniqid();
        mkdir($this->destination);

        // Patterns allow for the cmd.exe wrapper Symfony adds on Windows.
        Process::preventStrayProcesses();
        Process::fake([
            '*mysqldump*' => function (PendingProcess $process) {
                $file = collect($process->command)->first(fn ($arg) => str_starts_with($arg, '--result-file='));
                file_put_contents(substr($file, strlen('--result-file=')), "-- dump\n");

                return Process::result();
            },
            '*mysql*' => Process::result(),
        ]);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->destination);

        foreach (glob(app(Backup::class)->filesRoot().'-sebelum-restore-*') ?: [] as $aside) {
            (new Filesystem)->deleteDirectory($aside);
        }

        parent::tearDown();
    }

    private function backupFolder(): string
    {
        $this->artisan('backup:create', ['destination' => $this->destination])->assertSuccessful();

        return glob($this->destination.DIRECTORY_SEPARATOR.'outreach-backup-*')[0];
    }

    public function test_a_backup_holds_the_database_every_private_file_and_a_manifest()
    {
        $folder = $this->backupFolder();

        $manifest = json_decode((string) file_get_contents($folder.'/manifest.json'), true);
        $this->assertSame(['attachments/1/ktp.jpg', 'signatures/1.png'], array_keys($manifest['files']));
        $this->assertSame('gambar ktp', file_get_contents($folder.'/files/attachments/1/ktp.jpg'));
        $this->assertSame(2, $manifest['database']['rows']['users']);
        $this->assertSame(hash_file('sha256', $folder.'/database.sql'), $manifest['database']['sha256']);
        Process::assertRan(fn (PendingProcess $process) => $process->command[0] === 'mysqldump'
            && in_array('--single-transaction', $process->command, true)
            && ! collect($process->command)->contains(fn ($arg) => str_starts_with($arg, '--password')));
    }

    public function test_a_missing_destination_is_refused()
    {
        $this->artisan('backup:create', ['destination' => $this->destination.'/tidak-ada'])->assertFailed();

        Process::assertNothingRan();
    }

    public function test_restore_checks_the_backup_and_keeps_the_current_files_aside()
    {
        $folder = $this->backupFolder();
        Storage::disk('local')->delete('signatures/1.png');
        Storage::disk('local')->put('attachments/2/baru.jpg', 'berkas setelah backup');

        $this->artisan('backup:restore', ['folder' => $folder, '--force' => true])->assertSuccessful();

        $this->assertSame(['attachments/1/ktp.jpg', 'signatures/1.png'], Storage::disk('local')->allFiles());
        $aside = glob(app(Backup::class)->filesRoot().'-sebelum-restore-*')[0];
        $this->assertFileExists($aside.'/attachments/2/baru.jpg');
        Process::assertRan(fn (PendingProcess $process) => $process->command[0] === 'mysql' && $process->input === "-- dump\n");
    }

    public function test_a_damaged_backup_is_refused_before_anything_changes()
    {
        $folder = $this->backupFolder();
        file_put_contents($folder.'/files/signatures/1.png', 'rusak');
        Storage::disk('local')->put('attachments/2/baru.jpg', 'tetap ada');

        $this->artisan('backup:restore', ['folder' => $folder, '--force' => true])
            ->expectsOutputToContain('Berkas signatures/1.png hilang atau isinya berbeda.')
            ->assertFailed();

        Storage::disk('local')->assertExists('attachments/2/baru.jpg');
        Process::assertDidntRun(fn (PendingProcess $process) => $process->command[0] === 'mysql');
    }

    public function test_restore_asks_for_confirmation()
    {
        $folder = $this->backupFolder();

        $this->artisan('backup:restore', ['folder' => $folder])
            ->expectsConfirmation('Seluruh isi database '.config('database.connections.mysql.database').' dan berkas privat akan diganti dengan isi backup ini. Lanjutkan?', 'no')
            ->assertFailed();

        Process::assertDidntRun(fn (PendingProcess $process) => $process->command[0] === 'mysql');
    }
}
