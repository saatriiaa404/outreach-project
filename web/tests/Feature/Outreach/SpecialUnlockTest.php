<?php

namespace Tests\Feature\Outreach;

use App\Actions\Outreach\ApproveOutreach;
use App\Actions\Outreach\FinalizeOutreach;
use App\Actions\Outreach\SaveOutreachStep;
use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Enums\SpecialUnlockRequestStatus;
use App\Models\AuditLog;
use App\Models\HistoryEvent;
use App\Models\InAppNotification;
use App\Models\Outreach;
use App\Models\OutreachVersion;
use App\Models\SpecialUnlockRequest;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class SpecialUnlockTest extends TestCase
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
        DemoSeeder::signatureTemplate($this->kepala);
    }

    private function submitted(): Outreach
    {
        $outreach = $this->ready();
        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);

        return $outreach->refresh();
    }

    private function completed(): Outreach
    {
        $outreach = $this->submitted();
        app(ApproveOutreach::class)->handle($outreach, $this->kepala, (int) $outreach->latestVersion()->value('id'));

        return $outreach->refresh();
    }

    private function unlock(Outreach $outreach, string $reason = 'Nomor telepon salah ketik menurut Petugas.')
    {
        return $this->actingAs($this->admin)->post(route('outreaches.special-unlock', $outreach), [
            'reason' => $reason,
            'edit_version' => $outreach->refresh()->edit_version,
        ]);
    }

    private function narasi(User $user, Outreach $outreach, string $harapan)
    {
        return $this->actingAs($user)->put(route('outreaches.update', $outreach), [
            'step' => 'narasi',
            'edit_version' => $outreach->refresh()->edit_version,
            'upaya_dilakukan' => 'Pendampingan rutin.',
            'harapan_klien' => $harapan,
            'uraian_alasan_intervensi' => 'Rumah tidak layak huni.',
        ]);
    }

    public function test_admin_opens_special_unlock_with_a_reason_without_changing_the_status()
    {
        $outreach = $this->completed();
        $editVersion = $outreach->edit_version;

        $this->unlock($outreach)->assertSessionHasNoErrors()->assertRedirect(route('outreaches.edit', $outreach));

        $outreach->refresh();
        $this->assertSame(OutreachStatus::Selesai, $outreach->status, 'Special Unlock is not a status.');
        $this->assertSame($editVersion + 1, $outreach->edit_version);

        $event = HistoryEvent::query()->where('event_type', HistoryEventType::SpecialUnlocked)->sole();
        $this->assertSame($this->admin->id, $event->actor_id);
        $this->assertSame('Nomor telepon salah ketik menurut Petugas.', $event->reason);
        $this->assertSame($outreach->latestVersion()->value('id'), $event->outreach_version_id);

        $audit = AuditLog::query()->sole();
        $this->assertSame('SPECIAL_UNLOCK', $audit->action);
        $this->assertSame('outreaches', $audit->target_type);
        $this->assertSame($outreach->id, $audit->target_id);

        // Already open: no second session.
        $this->unlock($outreach)->assertForbidden();
    }

    public function test_only_admin_unlocks_submitted_or_completed_outreach_with_a_reason()
    {
        $outreach = $this->submitted();

        foreach ([$outreach->petugas, $this->kepala] as $user) {
            $this->actingAs($user)->post(route('outreaches.special-unlock', $outreach), ['reason' => 'x', 'edit_version' => $outreach->edit_version])->assertForbidden();
        }

        $this->unlock($outreach, ' ')->assertSessionHasErrors('reason');

        $draft = $this->ready('BAZNAS', '2026-10-20');
        $this->unlock($draft)->assertForbidden();

        $this->actingAs($this->admin)->post(route('outreaches.special-unlock', $outreach), ['reason' => 'Koreksi.', 'edit_version' => $outreach->edit_version - 1])
            ->assertSessionHasErrors('edit_version');

        $this->assertSame(0, HistoryEvent::query()->where('event_type', HistoryEventType::SpecialUnlocked)->count());
    }

    public function test_admin_edits_only_while_unlocked_and_every_change_is_recorded_with_the_reason()
    {
        $outreach = $this->completed();
        $this->narasi($this->admin, $outreach, 'Diubah tanpa unlock.')->assertForbidden();

        $this->unlock($outreach);
        $this->actingAs($this->admin)->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'narasi']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('unlockSession.reason', 'Nomor telepon salah ketik menurut Petugas.'));

        $this->narasi($this->admin, $outreach, 'Atap diperbaiki sebelum musim hujan.')->assertSessionHasNoErrors();

        $event = HistoryEvent::query()->where('event_type', HistoryEventType::SpecialUnlockEdited)->sole();
        $this->assertSame('Nomor telepon salah ketik menurut Petugas.', $event->reason);
        $this->assertSame('Atap diperbaiki sebelum musim hujan.', $event->field_changes['harapan_klien']['to']);
        $this->assertSame(0, HistoryEvent::query()->where('event_type', HistoryEventType::SectionSaved)->where('actor_id', $this->admin->id)->count());

        // The Petugas still cannot edit, and the Admin never signs.
        $this->narasi($outreach->petugas, $outreach, 'Petugas')->assertForbidden();
        $this->actingAs($this->admin)->delete(route('outreaches.signatures.destroy', [$outreach, 'kifayah']), ['edit_version' => $outreach->refresh()->edit_version])->assertForbidden();
        $this->actingAs($this->admin)->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'tanda_tangan']))
            ->assertInertia(fn (Assert $page) => $page->where('canSign', false));
    }

    public function test_kepala_cannot_decide_while_the_admin_corrects()
    {
        $outreach = $this->submitted();
        $versionId = (int) $outreach->latestVersion()->value('id');
        $this->unlock($outreach);

        $this->actingAs($this->kepala)->post(route('outreaches.approve', $outreach), ['outreach_version_id' => $versionId])
            ->assertSessionHasErrors(['approve' => 'Admin sedang melakukan Special Unlock pada Outreach ini. Keputusan dapat diambil setelah Admin melakukan Lock atau Lock & Ajukan.']);
        $this->actingAs($this->kepala)->post(route('outreaches.return', $outreach), ['outreach_version_id' => $versionId, 'note' => 'x'])
            ->assertSessionHasErrors('return');
        $this->actingAs($this->kepala)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page->where('specialUnlock.session.admin', $this->admin->name));

        $this->assertSame(OutreachStatus::MenungguPengesahan, $outreach->refresh()->status);
    }

    public function test_lock_hands_back_to_the_petugas_without_a_new_version()
    {
        $outreach = $this->completed();
        $this->actingAs($outreach->petugas)->post(route('outreaches.special-unlock-requests.store', $outreach), ['reason' => 'Telepon salah.']);
        $this->unlock($outreach);
        $this->assertSame(SpecialUnlockRequestStatus::Processing, SpecialUnlockRequest::query()->sole()->status, 'Unlocking takes up the waiting request.');

        $this->actingAs($this->admin)->post(route('outreaches.special-unlock.lock', $outreach), ['note' => '', 'edit_version' => $outreach->refresh()->edit_version])
            ->assertSessionHasErrors('note');
        $this->actingAs($this->admin)->post(route('outreaches.special-unlock.lock', $outreach), [
            'note' => 'Minta Warga tanda tangan ulang Kifayah.',
            'edit_version' => $outreach->refresh()->edit_version,
        ])->assertSessionHasNoErrors()->assertRedirect(route('outreaches.show', $outreach));

        $outreach->refresh();
        $this->assertSame(OutreachStatus::PerluPerbaikan, $outreach->status);
        $this->assertSame([OutreachVersion::APPROVED], OutreachVersion::query()->pluck('status')->all(), 'Lock creates no version (D-031).');
        $event = HistoryEvent::query()->where('event_type', HistoryEventType::SpecialUnlockLocked)->sole();
        $this->assertSame('Minta Warga tanda tangan ulang Kifayah.', $event->reason);
        $this->assertSame(['SPECIAL_UNLOCK', 'SPECIAL_UNLOCK_LOCK'], AuditLog::query()->orderBy('id')->pluck('action')->all());

        $request = SpecialUnlockRequest::query()->sole();
        $this->assertSame(SpecialUnlockRequestStatus::Completed, $request->status);
        $this->assertNotNull($request->processed_at);

        // The session is closed; the Petugas continues and sees why.
        $this->narasi($this->admin, $outreach, 'Admin lagi')->assertForbidden();
        $this->actingAs($outreach->petugas)->get(route('outreaches.edit', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->where('returned.kind', 'SPECIAL_UNLOCK_LOCKED')
                ->where('returned.note', 'Minta Warga tanda tangan ulang Kifayah.')
                ->where('returned.by', $this->admin->name));

        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);
        $this->assertSame(2, $outreach->latestVersion()->value('version_number'));
    }

    public function test_lock_and_submit_creates_a_new_version_for_the_kepala()
    {
        $outreach = $this->completed();
        $this->unlock($outreach);
        $this->narasi($this->admin, $outreach, 'Harapan setelah koreksi.');

        $this->actingAs($this->admin)->post(route('outreaches.special-unlock.submit', $outreach), ['edit_version' => $outreach->refresh()->edit_version])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('outreaches.show', $outreach));

        $outreach->refresh();
        $this->assertSame(OutreachStatus::MenungguPengesahan, $outreach->status);
        $this->assertSame([OutreachVersion::APPROVED, OutreachVersion::PENDING_APPROVAL], OutreachVersion::query()->orderBy('version_number')->pluck('status')->all());
        $version = $outreach->latestVersion()->firstOrFail();
        $this->assertSame('Harapan setelah koreksi.', $version->snapshot_data['narrative']['harapan_klien']);
        $this->assertSame($this->admin->id, $version->created_by);

        $event = HistoryEvent::query()->where('event_type', HistoryEventType::SpecialUnlockSubmitted)->sole();
        $this->assertSame($version->id, $event->outreach_version_id);
        $this->assertSame(1, InAppNotification::query()->where('type', InAppNotification::OUTREACH_FINALIZED)->where('outreach_version_id', $version->id)->count());
        $this->assertSame(['SPECIAL_UNLOCK', 'SPECIAL_UNLOCK_SUBMIT'], AuditLog::query()->orderBy('id')->pluck('action')->all());

        // The Admin never approves; the Kepala decides on the new version.
        $this->actingAs($this->admin)->post(route('outreaches.approve', $outreach), ['outreach_version_id' => $version->id])->assertForbidden();
        $this->actingAs($this->kepala)->post(route('outreaches.approve', $outreach), ['outreach_version_id' => $version->id])->assertSessionHasNoErrors();
        $this->assertSame(OutreachStatus::Selesai, $outreach->refresh()->status);
    }

    public function test_a_change_to_signed_data_forces_lock_instead_of_submit()
    {
        $outreach = $this->completed();
        $this->unlock($outreach);
        app(SaveOutreachStep::class)->handle($outreach, $this->admin, 'klien', ['no_telp' => '080000000099'], $outreach->refresh()->edit_version);

        $this->actingAs($this->admin)->post(route('outreaches.special-unlock.submit', $outreach), ['edit_version' => $outreach->refresh()->edit_version])
            ->assertSessionHasErrors('finalize');
        $this->assertSame(OutreachStatus::Selesai, $outreach->refresh()->status);
        $this->assertSame(1, OutreachVersion::query()->count());

        $this->actingAs($this->admin)->post(route('outreaches.special-unlock.lock', $outreach), ['note' => 'Tanda tangan ulang Mustahik dan Kifayah.', 'edit_version' => $outreach->edit_version])
            ->assertSessionHasNoErrors();
        $this->assertSame(OutreachStatus::PerluPerbaikan, $outreach->refresh()->status);
    }

    public function test_admin_may_change_the_source_while_unlocked()
    {
        $outreach = $this->submitted();
        $this->assertFalse($this->admin->can('changeSourceType', $outreach));

        $this->unlock($outreach);

        $this->assertTrue($this->admin->can('changeSourceType', $outreach->refresh()));
        $this->assertFalse($outreach->petugas->can('changeSourceType', $outreach));
    }
}
