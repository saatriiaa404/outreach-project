<?php

namespace Tests\Feature;

use App\Enums\OutreachStatus;
use App\Enums\SpecialUnlockRequestStatus;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\SpecialUnlockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    private function total(string $status): \Closure
    {
        return fn ($statuses) => collect($statuses)->firstWhere('status', $status)['total'];
    }

    public function test_petugas_sees_only_their_own_outreach_with_revisions_first()
    {
        $petugas = User::factory()->create();
        Outreach::factory()->for($petugas, 'petugas')->create(['nama' => 'Draft Saya']);
        Outreach::factory()->for($petugas, 'petugas')->status(OutreachStatus::PerluPerbaikan)->create(['nama' => 'Revisi Saya']);
        Outreach::factory()->count(3)->create();

        $this->actingAs($petugas)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('role', 'PETUGAS')
                ->has('statuses', 4)
                ->where('statuses', fn ($statuses) => collect($statuses)->sum('total') === 2)
                ->where('focus.0.nama', 'Revisi Saya')
                ->where('focus.1.nama', 'Draft Saya')
                ->where('admin', null));
    }

    public function test_kepala_sees_the_queue_and_completed_without_drafts()
    {
        Outreach::factory()->count(2)->create();
        Outreach::factory()->status(OutreachStatus::MenungguPengesahan)->create();
        Outreach::factory()->status(OutreachStatus::Selesai)->count(2)->create();

        $this->actingAs(User::factory()->kepala()->create())->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('statuses', fn ($statuses) => collect($statuses)->pluck('status')->all() === ['MENUNGGU_PENGESAHAN', 'SELESAI'])
                ->where('statuses', fn ($statuses) => ($this->total('SELESAI'))($statuses) === 2)
                ->has('focus', 1)
                ->where('focus.0.status', 'MENUNGGU_PENGESAHAN'));
    }

    public function test_admin_sees_every_status_requests_and_archive()
    {
        Outreach::factory()->count(2)->create();
        $completed = Outreach::factory()->status(OutreachStatus::Selesai)->create();
        $request = new SpecialUnlockRequest;
        $request->forceFill([
            'outreach_id' => $completed->id, 'requester_id' => $completed->petugas_id, 'requester_role_snapshot' => 'PETUGAS',
            'reason' => 'Koreksi.', 'status' => SpecialUnlockRequestStatus::Pending,
        ])->save();

        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('statuses', fn ($statuses) => ($this->total('DRAFT'))($statuses) === 2)
                ->where('admin.special_unlock_open', 1)
                // A factory SELESAI row has no approved version, so nothing to archive.
                ->where('admin.archive_pending', 0));
    }

    public function test_admin_charts_count_by_outreach_date_type_and_source()
    {
        $rutilahu = OutreachType::factory()->create(['name' => 'Rutilahu']);
        $beasiswa = OutreachType::factory()->create(['name' => 'Beasiswa']);
        $baznas = OutreachSource::factory()->create(['name' => 'Baznas']);
        Outreach::factory()->count(2)->for($rutilahu, 'type')->for($baznas, 'source')->create(['outreach_date' => '2026-09-03']);
        Outreach::factory()->for($beasiswa, 'type')->for($baznas, 'source')->create(['outreach_date' => '2026-09-30']);
        Outreach::factory()->for($beasiswa, 'type')->for($baznas, 'source')->create(['outreach_date' => '2026-08-15']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('dashboard', ['bulan' => '2026-09']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('charts.month', '2026-09')
                ->where('charts.total', 3)
                ->has('charts.per_day', 30)
                ->where('charts.per_day.2.total', 2)
                ->where('charts.per_day.29.total', 1)
                ->where('charts.per_type.0', ['label' => 'Rutilahu', 'total' => 2])
                ->where('charts.per_type.1', ['label' => 'Beasiswa', 'total' => 1])
                ->where('charts.per_source', fn ($rows) => collect($rows)->firstWhere('label', 'Baznas')['total'] === 3)
                ->has('charts.trend', 12)
                ->where('charts.trend.11', fn ($month) => $month['month'] === '2026-09' && $month['created'] === 3)
                ->where('charts.trend.10.created', 1));

        // A malformed month falls back to the current one; other roles get no charts.
        $this->actingAs($admin)->get(route('dashboard', ['bulan' => '2026-13']))
            ->assertInertia(fn (Assert $page) => $page->where('charts.month', now('Asia/Jakarta')->format('Y-m')));
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('charts', null));
    }
}
