<?php

namespace Tests\Feature\Outreach;

use App\Actions\Outreach\FinalizeOutreach;
use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\InAppNotification;
use App\Models\Outreach;
use App\Models\OutreachApproval;
use App\Models\OutreachVersion;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class ReturnOutreachTest extends TestCase
{
    use BuildsReadyOutreach;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
    }

    private function submitted(string $sourceCode = 'BAZNAS', string $date = '2026-10-27'): Outreach
    {
        $outreach = $this->ready($sourceCode, $date);
        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);

        return $outreach->refresh();
    }

    private function returnAs(User $user, Outreach $outreach, string $note = 'Foto rumah kurang jelas, mohon diganti.', ?int $versionId = null)
    {
        return $this->actingAs($user)->post(route('outreaches.return', $outreach), [
            'outreach_version_id' => $versionId ?? $outreach->latestVersion()->value('id'),
            'note' => $note,
        ]);
    }

    public function test_kepala_returns_the_pending_version_with_a_note()
    {
        $kepala = User::factory()->kepala()->create();
        $outreach = $this->submitted();
        $editVersion = $outreach->edit_version;

        $this->returnAs($kepala, $outreach)->assertSessionHasNoErrors()->assertRedirect(route('outreaches.index'));

        $outreach->refresh();
        $this->assertSame(OutreachStatus::PerluPerbaikan, $outreach->status);
        $this->assertSame($editVersion + 1, $outreach->edit_version);

        $version = OutreachVersion::query()->sole();
        $this->assertSame(OutreachVersion::RETURNED, $version->status, 'The version stays, only its status follows the decision.');

        $approval = OutreachApproval::query()->sole();
        $this->assertSame($version->id, $approval->outreach_version_id);
        $this->assertSame($kepala->id, $approval->kepala_id);
        $this->assertSame(OutreachApproval::RETURNED, $approval->decision);
        $this->assertSame('Foto rumah kurang jelas, mohon diganti.', $approval->note);
        $this->assertNull($approval->head_signature_template_id, 'Return needs no Kepala signature.');

        $event = HistoryEvent::query()->where('event_type', HistoryEventType::Returned)->sole();
        $this->assertSame($kepala->id, $event->actor_id);
        $this->assertSame($version->id, $event->outreach_version_id);
        $this->assertSame($approval->id, $event->approval_id);
        $this->assertSame('Foto rumah kurang jelas, mohon diganti.', $event->reason);

        $notification = InAppNotification::query()->where('type', InAppNotification::OUTREACH_RETURNED)->sole();
        $this->assertSame($outreach->petugas_id, $notification->user_id);
        $this->assertSame($approval->id, $notification->approval_id);
        $this->assertStringContainsString('Foto rumah kurang jelas', $notification->message);
    }

    public function test_a_note_is_mandatory()
    {
        $kepala = User::factory()->kepala()->create();
        $outreach = $this->submitted();

        foreach (['', '   '] as $note) {
            $this->returnAs($kepala, $outreach, $note)->assertSessionHasErrors('note');
        }

        $this->assertSame(OutreachStatus::MenungguPengesahan, $outreach->refresh()->status);
        $this->assertSame(0, OutreachApproval::query()->count());
        $this->assertSame(0, InAppNotification::query()->where('type', InAppNotification::OUTREACH_RETURNED)->count());
    }

    public function test_only_kepala_may_return_and_only_a_pending_submission()
    {
        $outreach = $this->submitted();

        foreach ([$outreach->petugas, User::factory()->create(), User::factory()->admin()->create()] as $user) {
            $this->returnAs($user, $outreach)->assertForbidden();
        }
        // An inactive Kepala is signed out before reaching the action.
        $this->returnAs(User::factory()->kepala()->inactive()->create(), $outreach)->assertRedirect();
        $this->assertSame(0, OutreachApproval::query()->count());

        $kepala = User::factory()->kepala()->create();
        $this->returnAs($kepala, $outreach)->assertSessionHasNoErrors();
        $this->returnAs($kepala, $outreach->refresh())->assertForbidden();
        $this->returnAs($kepala, $this->ready('BAZNAS', '2026-10-20'))->assertForbidden();

        $this->assertSame(1, OutreachApproval::query()->count());
    }

    public function test_a_stale_version_is_rejected()
    {
        $kepala = User::factory()->kepala()->create();
        $outreach = $this->submitted();

        $this->returnAs($kepala, $outreach, versionId: 999)->assertSessionHasErrors('return');

        $this->assertSame(OutreachStatus::MenungguPengesahan, $outreach->refresh()->status);
        $this->assertSame(0, OutreachApproval::query()->count());
    }

    public function test_finalisasi_ulang_after_return_keeps_the_returned_version_and_its_decision()
    {
        $kepala = User::factory()->kepala()->create();
        $outreach = $this->submitted();
        $this->returnAs($kepala, $outreach);

        $outreach->refresh();
        $this->assertTrue($outreach->petugas->can('update', $outreach));
        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);

        $this->assertSame(OutreachStatus::MenungguPengesahan, $outreach->refresh()->status);
        $this->assertSame([OutreachVersion::RETURNED, OutreachVersion::PENDING_APPROVAL], OutreachVersion::query()->orderBy('version_number')->pluck('status')->all());
        $this->assertSame(2, $outreach->latestVersion()->value('version_number'));
        $this->assertSame(1, OutreachApproval::query()->count());

        // The new version is pending again, so the Kepala may decide on it.
        $this->returnAs($kepala, $outreach, 'Kedua kalinya.')->assertSessionHasNoErrors();
        $this->assertSame(2, OutreachApproval::query()->count());
    }

    public function test_approval_decisions_are_immutable()
    {
        $this->returnAs(User::factory()->kepala()->create(), $this->submitted());
        $approval = OutreachApproval::query()->sole();

        $this->expectException(LogicException::class);
        $approval->forceFill(['note' => 'Diubah'])->save();
    }

    public function test_kepala_reviews_the_submitted_version_read_only()
    {
        $kepala = User::factory()->kepala()->create();
        $outreach = $this->submitted();
        // Current data changes after Finalisasi do not reach the review.
        $outreach->forceFill(['nama' => 'Nama Setelah Finalisasi'])->save();

        $this->actingAs($kepala)->get(route('outreaches.show', $outreach))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('outreach/Show')
                ->where('outreach.status', 'MENUNGGU_PENGESAHAN')
                ->where('canReturn', true)
                ->where('canEdit', false)
                ->where('version.number', 1)
                ->where('version.status', 'PENDING_APPROVAL')
                ->where('version.document.sections.0.rows.0.value', 'Warga Demo')
                ->has('version.document.attachments', 4)
                ->has('version.mustahik.answers', 25)
                ->has('version.kifayah.fields', 15)
                ->has('version.signatures', 2)
                ->has('history', 1)
                ->where('history.0.type', 'FINALIZED'));
    }

    public function test_kepala_sees_only_status_and_return_notes_while_it_is_revised()
    {
        $kepala = User::factory()->kepala()->create();
        $outreach = $this->submitted();
        $this->returnAs($kepala, $outreach);

        $this->actingAs($kepala)->get(route('outreaches.show', $outreach))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('outreach.status', 'PERLU_PERBAIKAN')
                ->where('outreach.nama', null)
                ->where('version', null)
                ->where('canReturn', false)
                ->where('history.1.type', 'RETURNED')
                ->where('history.1.reason', 'Foto rumah kurang jelas, mohon diganti.'));

        $version = OutreachVersion::query()->sole();
        $attachment = $version->attachments()->first();
        $this->actingAs($kepala)->get(route('outreaches.versions.attachments.show', [$outreach, $version, $attachment]))->assertForbidden();
    }

    public function test_review_access_follows_visibility()
    {
        $kepala = User::factory()->kepala()->create();
        $draft = $this->ready('BAZNAS', '2026-10-20');
        $outreach = $this->submitted();

        $this->actingAs($kepala)->get(route('outreaches.show', $draft))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('outreaches.show', $outreach))->assertForbidden();
        $this->actingAs($outreach->petugas)->get(route('outreaches.show', $outreach))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canReturn', false)->where('version.number', 1));
        $this->actingAs(User::factory()->admin()->create())->get(route('outreaches.show', $outreach))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canReturn', false));
    }

    public function test_version_files_are_served_only_with_full_access()
    {
        $kepala = User::factory()->kepala()->create();
        $outreach = $this->submitted();
        $version = OutreachVersion::query()->sole();
        $attachment = $version->attachments()->first();

        $this->actingAs($kepala)->get(route('outreaches.versions.attachments.show', [$outreach, $version, $attachment]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($kepala)->get(route('outreaches.versions.signatures.show', [$outreach, $version, 'kifayah']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
        $this->actingAs(User::factory()->create())->get(route('outreaches.versions.attachments.show', [$outreach, $version, $attachment]))
            ->assertForbidden();

        $other = $this->submitted('BAZNAS', '2026-10-20');
        $this->actingAs($kepala)->get(route('outreaches.versions.attachments.show', [$other, $version, $attachment]))->assertNotFound();
    }

    public function test_the_kepala_starts_on_the_queue_oldest_submission_first()
    {
        $kepala = User::factory()->kepala()->create();
        $this->travelTo('2026-10-27 08:00');
        $first = $this->submitted('BAZNAS', '2026-10-20');
        $this->travelTo('2026-10-27 09:00');
        $second = $this->submitted('BAZNAS', '2026-10-21');
        $this->returnAs($kepala, $second);
        $this->travelTo('2026-10-27 10:00');
        $third = $this->submitted('BAZNAS', '2026-10-22');
        $this->ready('BAZNAS', '2026-10-23');

        $this->actingAs($kepala)->get(route('outreaches.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('status', 'MENUNGGU_PENGESAHAN')
                ->where('statuses', ['MENUNGGU_PENGESAHAN', 'PERLU_PERBAIKAN', 'SELESAI'])
                ->has('outreaches.data', 2)
                ->where('outreaches.data.0.id', $first->id)
                ->where('outreaches.data.1.id', $third->id)
                ->where('outreaches.data.0.can_open', true));

        $this->actingAs($kepala)->get(route('outreaches.index', ['status' => '']))
            ->assertInertia(fn (Assert $page) => $page->where('status', null)->has('outreaches.data', 3));
    }

    public function test_the_petugas_sees_the_return_note_while_revising()
    {
        $kepala = User::factory()->kepala()->create();
        $outreach = $this->submitted();
        $this->returnAs($kepala, $outreach);

        foreach (['klien', 'review'] as $step) {
            $this->actingAs($outreach->petugas)->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => $step]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('outreach.status', 'PERLU_PERBAIKAN')
                    ->where('returned.note', 'Foto rumah kurang jelas, mohon diganti.')
                    ->where('returned.kepala', $kepala->name));
        }
    }
}
