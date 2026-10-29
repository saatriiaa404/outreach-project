<?php

namespace Tests\Feature;

use App\Actions\Outreach\ApproveOutreach;
use App\Actions\Outreach\FinalizeOutreach;
use App\Models\ArchiveRecord;
use App\Models\AuditLog;
use App\Models\Outreach;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use BuildsReadyOutreach;
    use RefreshDatabase;

    private User $admin;

    private User $kepala;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
        $this->kepala = User::factory()->kepala()->create();
    }

    private function submitted(string $date = '2026-10-27'): Outreach
    {
        $outreach = $this->ready('BAZNAS', $date);
        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);

        return $outreach->refresh();
    }

    private function approved(string $date = '2026-10-27'): Outreach
    {
        $outreach = $this->submitted($date);
        // Created here so it is valid at the (possibly travelled) approval time.
        DemoSeeder::signatureTemplate($this->kepala);
        app(ApproveOutreach::class)->handle($outreach, $this->kepala, (int) $outreach->latestVersion()->value('id'));

        return $outreach->refresh();
    }

    private function mark(User $user, Outreach $outreach, array $data = [])
    {
        return $this->actingAs($user)->post(route('archive.store', $outreach), [
            'outreach_version_id' => $outreach->latestVersion()->value('id'),
            'external_system' => 'E-Arsip',
            'archive_reference' => 'ARS/001',
            'archived_at' => now()->toDateString(),
            ...$data,
        ]);
    }

    public function test_only_admin_works_with_archives()
    {
        $outreach = $this->approved();

        foreach ([$outreach->petugas, $this->kepala] as $user) {
            $this->actingAs($user)->get(route('archive.index'))->assertForbidden();
            $this->mark($user, $outreach)->assertForbidden();
        }

        $this->assertSame(0, ArchiveRecord::query()->count());
    }

    public function test_admin_marks_the_approved_version_and_it_leaves_the_pending_list()
    {
        $this->travelTo('2026-10-29 09:00');
        $outreach = $this->approved();
        $this->submitted('2026-10-20');

        $this->actingAs($this->admin)->get(route('archive.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('archive/Index')
                ->where('filter', 'pending')
                ->has('rows', 1)
                ->where('rows.0.approved_at', '2026-10-29')
                ->where('rows.0.deadline', '2026-11-29')
                ->where('rows.0.overdue', false));

        $this->mark($this->admin, $outreach, ['archive_reference' => ''])->assertSessionHasNoErrors();

        $record = ArchiveRecord::query()->sole();
        $this->assertSame($outreach->latestVersion()->value('id'), $record->outreach_version_id);
        $this->assertSame('E-Arsip', $record->external_system);
        $this->assertNull($record->archive_reference);
        $this->assertNull($record->verified_at);
        $this->assertSame('ARCHIVE_MARKED', AuditLog::query()->sole()->action);

        $this->actingAs($this->admin)->get(route('archive.index'))->assertInertia(fn (Assert $page) => $page->has('rows', 0));
        $this->actingAs($this->admin)->get(route('archive.index', ['filter' => 'archived']))
            ->assertInertia(fn (Assert $page) => $page->has('rows', 1)->where('rows.0.record.external_system', 'E-Arsip'));

        $this->mark($this->admin, $outreach)->assertSessionHasErrors(['archive' => 'Versi ini sudah ditandai diarsipkan.']);
    }

    public function test_only_completed_outreach_can_be_archived()
    {
        $pending = $this->submitted();

        $this->mark($this->admin, $pending)->assertSessionHasErrors('archive');
        $this->assertSame(0, ArchiveRecord::query()->count());
    }

    public function test_marks_need_a_system_and_a_date_not_in_the_future()
    {
        $outreach = $this->approved();

        $this->mark($this->admin, $outreach, ['external_system' => ''])->assertSessionHasErrors('external_system');
        $this->mark($this->admin, $outreach, ['archived_at' => now()->addDay()->toDateString()])->assertSessionHasErrors('archived_at');
        $this->assertSame(0, ArchiveRecord::query()->count());
    }

    public function test_corrections_and_verification_are_audited()
    {
        $outreach = $this->approved();
        $this->mark($this->admin, $outreach);
        $record = ArchiveRecord::query()->sole();

        $this->actingAs($this->admin)->put(route('archive.update', $record), [
            'external_system' => 'E-Surat', 'archive_reference' => 'ARS/002', 'archived_at' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame('E-Surat', $record->external_system);
        $correction = AuditLog::query()->where('action', 'ARCHIVE_CORRECTED')->sole();
        $this->assertSame('E-Arsip', $correction->before_data['external_system']);
        $this->assertSame('ARS/002', $correction->after_data['archive_reference']);

        $this->actingAs($this->admin)->post(route('archive.verify', $record))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('archive.verify', $record))->assertSessionHasNoErrors();
        $this->assertNotNull($record->refresh()->verified_at);
        $this->assertSame(1, AuditLog::query()->where('action', 'ARCHIVE_VERIFIED')->count(), 'Verifying twice records once.');
    }

    public function test_overdue_marks_after_one_month()
    {
        $this->travelTo('2026-09-15 09:00');
        $this->approved('2026-09-14');
        $this->travelTo('2026-10-16 09:00');

        $this->actingAs($this->admin)->get(route('archive.index'))
            ->assertInertia(fn (Assert $page) => $page->where('rows.0.deadline', '2026-10-15')->where('rows.0.overdue', true));
    }
}
