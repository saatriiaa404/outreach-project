<?php

namespace Tests\Feature\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\TestCase;

class CreateOutreachTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_open_the_create_page_with_active_master_data_only()
    {
        $active = OutreachSource::factory()->create(['name' => 'Aktif']);
        OutreachSource::factory()->create(['name' => 'Nonaktif', 'active' => false]);
        OutreachType::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('outreaches.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('outreach/Create')
                ->has('sources', 1)
                ->where('sources.0.id', $active->id)
                ->has('types', 1));
    }

    public function test_petugas_creates_a_draft_with_draft_code_and_history()
    {
        $petugas = User::factory()->create();
        $source = OutreachSource::factory()->create();
        $type = OutreachType::factory()->create();

        $response = $this->actingAs($petugas)
            ->post(route('outreaches.store'), [
                'source_id' => $source->id,
                'type_id' => $type->id,
                'outreach_date' => '2026-10-27',
            ])
            ->assertSessionHasNoErrors();

        $outreach = Outreach::query()->sole();
        $response->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'klien']));
        $this->assertSame(OutreachStatus::Draft, $outreach->status);
        $this->assertMatchesRegularExpression('/^DRAFT-[A-Z0-9]{12}$/', $outreach->draft_code);
        $this->assertNull($outreach->outreach_number);
        $this->assertSame($petugas->id, $outreach->petugas_id);
        $this->assertSame($source->id, $outreach->source_id);
        $this->assertSame($type->id, $outreach->type_id);
        $this->assertSame('2026-10-27', $outreach->outreach_date->toDateString());
        $this->assertSame(0, $outreach->edit_version);

        $event = HistoryEvent::query()->sole();
        $this->assertSame(HistoryEventType::Created, $event->event_type);
        $this->assertSame($petugas->id, $event->actor_id);
        $this->assertSame('PETUGAS', $event->actor_role_snapshot);
    }

    public function test_source_type_and_date_are_required_and_must_be_valid()
    {
        $inactiveSource = OutreachSource::factory()->create(['active' => false]);
        $inactiveType = OutreachType::factory()->create(['active' => false]);

        $this->actingAs(User::factory()->create())
            ->post(route('outreaches.store'), [])
            ->assertSessionHasErrors(['source_id', 'type_id', 'outreach_date']);

        $this->post(route('outreaches.store'), [
            'source_id' => $inactiveSource->id,
            'type_id' => $inactiveType->id,
            'outreach_date' => '27-10-2026',
        ])->assertSessionHasErrors(['source_id', 'type_id', 'outreach_date']);

        $this->assertSame(0, Outreach::query()->count());
    }

    public function test_kepala_and_admin_can_not_create_outreach()
    {
        $source = OutreachSource::factory()->create();
        $type = OutreachType::factory()->create();

        foreach ([User::factory()->kepala()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('outreaches.create'))->assertForbidden();
            $this->actingAs($user)->post(route('outreaches.store'), [
                'source_id' => $source->id,
                'type_id' => $type->id,
                'outreach_date' => '2026-10-27',
            ])->assertForbidden();
        }

        $this->assertSame(0, Outreach::query()->count());
    }

    public function test_index_lists_only_visible_outreach_and_hides_summary_only_content()
    {
        $petugas = User::factory()->create();
        Outreach::factory()->for($petugas, 'petugas')->create(['nama' => 'Warga Demo Satu']);
        Outreach::factory()->status(OutreachStatus::PerluPerbaikan)->create(['nama' => 'Warga Demo Dua']);

        $this->actingAs($petugas)
            ->get(route('outreaches.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('outreach/Index')
                ->has('outreaches.data', 1)
                ->where('outreaches.data.0.nama', 'Warga Demo Satu')
                ->where('canCreate', true));

        $this->actingAs(User::factory()->kepala()->create())
            ->get(route('outreaches.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('outreaches.data', 1)
                ->where('outreaches.data.0.status', 'PERLU_PERBAIKAN')
                ->where('outreaches.data.0.nama', null)
                ->where('canCreate', false));
    }

    public function test_history_events_are_append_only()
    {
        $outreach = Outreach::factory()->create();
        $event = HistoryEvent::record($outreach, $outreach->petugas, HistoryEventType::Created);

        try {
            $event->forceFill(['description' => 'diubah'])->save();
            $this->fail('Updating a history event must be rejected.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);

        $event->delete();
    }
}
