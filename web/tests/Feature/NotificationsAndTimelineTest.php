<?php

namespace Tests\Feature;

use App\Actions\Outreach\FinalizeOutreach;
use App\Actions\Outreach\ReturnOutreach;
use App\Actions\Outreach\SaveOutreachStep;
use App\Actions\Outreach\SpecialUnlockOutreach;
use App\Models\InAppNotification;
use App\Models\Outreach;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class NotificationsAndTimelineTest extends TestCase
{
    use BuildsReadyOutreach;
    use RefreshDatabase;

    private User $kepala;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
        $this->kepala = User::factory()->kepala()->create();
    }

    private function submitted(): Outreach
    {
        $outreach = $this->ready();
        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);

        return $outreach->refresh();
    }

    public function test_users_see_only_their_own_notifications_and_the_unread_count()
    {
        $outreach = $this->submitted();

        $this->actingAs($this->kepala)->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('notifications/Index')
                ->where('unreadNotifications', 1)
                ->has('notifications.data', 1)
                ->where('notifications.data.0.type', 'OUTREACH_FINALIZED')
                ->where('notifications.data.0.read', false));

        $this->actingAs($outreach->petugas)->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page->has('notifications.data', 0)->where('unreadNotifications', 0));

        $notification = InAppNotification::query()->sole();
        $this->actingAs($outreach->petugas)->get(route('notifications.open', $notification))->assertNotFound();
        $this->assertNull($notification->refresh()->read_at);
    }

    public function test_opening_marks_read_and_goes_to_the_outreach_as_the_user_may_see_it_now()
    {
        $outreach = $this->submitted();
        $notification = InAppNotification::query()->sole();

        $this->actingAs($this->kepala)->get(route('notifications.open', $notification))->assertRedirect(route('outreaches.show', $outreach));
        $this->assertNotNull($notification->refresh()->read_at);

        app(ReturnOutreach::class)->handle($outreach, $this->kepala, (int) $outreach->latestVersion()->value('id'), 'Perbaiki foto.');
        $returned = InAppNotification::query()->where('type', InAppNotification::OUTREACH_RETURNED)->sole();

        // The Petugas may edit again, so the notification leads to the stepper.
        $this->actingAs($outreach->petugas)->get(route('notifications.open', $returned))->assertRedirect(route('outreaches.edit', $outreach));
    }

    public function test_read_all_clears_only_the_users_own_unread()
    {
        $this->submitted();
        $other = User::factory()->kepala()->inactive()->create();
        $foreign = InAppNotification::query()->sole()->replicate()->forceFill(['user_id' => $other->id]);
        $foreign->save();

        $this->actingAs($this->kepala)->post(route('notifications.read-all'))->assertRedirect(route('notifications.index'));

        $this->assertSame(0, InAppNotification::query()->where('user_id', $this->kepala->id)->whereNull('read_at')->count());
        $this->assertNull($foreign->refresh()->read_at);
    }

    public function test_full_viewers_see_every_event_and_admin_changes_with_values()
    {
        $outreach = $this->submitted();
        $admin = User::factory()->admin()->create();
        $special = app(SpecialUnlockOutreach::class);
        $special->unlock($outreach, $admin, 'Telepon salah.', $outreach->refresh()->edit_version);
        app(SaveOutreachStep::class)->handle($outreach, $admin, 'narasi', ['harapan_klien' => 'Harapan baru'], $outreach->refresh()->edit_version);

        $this->actingAs($this->kepala)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->where('history', fn ($history) => collect($history)->pluck('type')->all() === ['FINALIZED', 'SPECIAL_UNLOCKED', 'SPECIAL_UNLOCK_EDITED'])
                ->where('history.2.step', 'Narasi')
                ->where('history.2.reason', 'Telepon salah.')
                ->where('history.2.changes.0.label', 'Harapan klien')
                ->where('history.2.changes.0.to', 'Harapan baru'));
    }

    public function test_kepala_sees_only_submission_events_while_it_is_revised()
    {
        $outreach = $this->submitted();
        app(ReturnOutreach::class)->handle($outreach, $this->kepala, (int) $outreach->latestVersion()->value('id'), 'Perbaiki foto.');
        app(SaveOutreachStep::class)->handle($outreach, $outreach->petugas, 'narasi', ['harapan_klien' => 'Rahasia revisi'], $outreach->refresh()->edit_version);

        $this->actingAs($this->kepala)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->where('history', fn ($history) => collect($history)->pluck('type')->all() === ['FINALIZED', 'RETURNED']));

        $this->actingAs($outreach->petugas)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->where('history', fn ($history) => collect($history)->pluck('type')->contains('SECTION_SAVED')));
    }
}
