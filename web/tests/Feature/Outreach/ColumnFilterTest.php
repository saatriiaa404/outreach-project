<?php

namespace Tests\Feature\Outreach;

use App\Enums\OutreachStatus;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Filters picked from the column headers of the Outreach list and the
 * dashboard table (D-072).
 */
class ColumnFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_filters_by_date_range_source_type_and_status()
    {
        $this->travelTo('2026-10-30 03:00:00');
        $admin = User::factory()->admin()->create();
        $baznas = OutreachSource::factory()->create();
        $rutilahu = OutreachType::factory()->create();
        $today = Outreach::factory()->for($baznas, 'source')->for($rutilahu, 'type')->create(['outreach_date' => '2026-10-30']);
        $lastWeek = Outreach::factory()->for($baznas, 'source')->create(['outreach_date' => '2026-10-25']);
        $lastYear = Outreach::factory()->status(OutreachStatus::Selesai)->create(['outreach_date' => '2025-12-31']);

        $ids = fn (array $query) => $this->actingAs($admin)->get(route('outreaches.index', $query))
            ->assertOk()
            ->viewData('page')['props']['outreaches']['data'];
        $codes = fn (array $query) => collect($ids($query))->pluck('id')->sort()->values()->all();

        $this->assertSame([$today->id], $codes(['tanggal' => 'hari-ini']));
        $this->assertSame([$today->id, $lastWeek->id], $codes(['tanggal' => '7-hari']));
        $this->assertSame([$today->id, $lastWeek->id], $codes(['tanggal' => 'tahun-ini']));
        $this->assertSame([$today->id, $lastWeek->id], $codes(['source' => $baznas->id]));
        $this->assertSame([$today->id], $codes(['source' => $baznas->id, 'type' => $rutilahu->id]));
        $this->assertSame([$lastYear->id], $codes(['status' => 'SELESAI']));
        // Unknown values are ignored rather than failing.
        $this->assertCount(3, $ids(['tanggal' => 'kemarin', 'source' => 'abc']));

        $this->actingAs($admin)->get(route('outreaches.index', ['tanggal' => '7-hari']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters', ['tanggal' => '7-hari', 'source' => null, 'type' => null])
                ->has('dateRanges', 5));
    }

    public function test_the_dashboard_table_filters_within_what_needs_attention()
    {
        $this->travelTo('2026-10-30 03:00:00');
        $petugas = User::factory()->create();
        $type = OutreachType::factory()->create();
        $draft = Outreach::factory()->for($petugas, 'petugas')->for($type, 'type')->create(['outreach_date' => '2026-10-30']);
        $revision = Outreach::factory()->for($petugas, 'petugas')->status(OutreachStatus::PerluPerbaikan)->create(['outreach_date' => '2026-09-01']);
        Outreach::factory()->for($petugas, 'petugas')->status(OutreachStatus::Selesai)->create(['outreach_date' => '2026-10-30']);

        $focus = fn (array $query) => collect($this->actingAs($petugas)->get(route('dashboard', $query))
            ->viewData('page')['props']['focus'])->pluck('id')->all();

        $this->assertSame([$revision->id, $draft->id], $focus([]));
        $this->assertSame([$draft->id], $focus(['tanggal' => 'bulan-ini']));
        $this->assertSame([$draft->id], $focus(['type' => $type->id]));
        $this->assertSame([$revision->id], $focus(['status' => 'PERLU_PERBAIKAN']));
        // A status outside the table's own set is ignored.
        $this->assertSame([$revision->id, $draft->id], $focus(['status' => 'SELESAI']));

        $this->actingAs($petugas)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filterOptions.statuses', ['PERLU_PERBAIKAN', 'DRAFT'])
                ->has('focus.0.outreach_date'));
    }
}
