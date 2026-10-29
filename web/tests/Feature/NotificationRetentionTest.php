<?php

namespace Tests\Feature;

use App\Actions\Outreach\FinalizeOutreach;
use App\Models\HistoryEvent;
use App\Models\InAppNotification;
use App\Models\Outreach;
use App\Models\OutreachVersion;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class NotificationRetentionTest extends TestCase
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

    /**
     * Two submissions: one 31 days ago, one today, each notifying the Kepala.
     */
    private function notifications(): void
    {
        $this->travelTo('2026-09-28 08:00');
        $old = $this->ready('BAZNAS', '2026-09-27');
        app(FinalizeOutreach::class)->handle($old, $old->petugas, $old->edit_version);
        $this->travelTo('2026-10-29 09:00');
        $new = $this->ready('BAZNAS', '2026-10-28');
        app(FinalizeOutreach::class)->handle($new, $new->petugas, $new->edit_version);
    }

    public function test_expired_notifications_are_hidden_before_the_cleanup()
    {
        $this->notifications();
        $expired = InAppNotification::query()->oldest('id')->firstOrFail();

        $this->actingAs($this->kepala)->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page->has('notifications.data', 1)->where('unreadNotifications', 1));
        $this->actingAs($this->kepala)->get(route('notifications.open', $expired))->assertNotFound();
    }

    public function test_prune_deletes_only_expired_notifications_and_nothing_else()
    {
        $this->notifications();

        $this->artisan('notifications:prune')->expectsOutput('1 notifikasi kedaluwarsa dihapus.')->assertSuccessful();

        $this->assertSame(1, InAppNotification::query()->count());
        $this->assertSame(2, Outreach::query()->count());
        $this->assertSame(2, OutreachVersion::query()->count());
        $this->assertSame(2, HistoryEvent::query()->where('event_type', 'FINALIZED')->count());
    }

    public function test_the_cleanup_is_scheduled_daily()
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'notifications:prune'));

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
    }
}
