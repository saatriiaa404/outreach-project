<?php

namespace Tests\Feature\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SaveOutreachStepTest extends TestCase
{
    use RefreshDatabase;

    private function draft(?User $petugas = null): Outreach
    {
        return Outreach::factory()->for($petugas ?? User::factory()->create(), 'petugas')->create();
    }

    public function test_creating_an_outreach_opens_the_data_klien_step()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'klien']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('outreach/Edit')
                ->where('step', 'klien')
                ->has('fields', 24)
                ->where('steps.0.key', 'identitas')
                ->where('steps.1.state', 'untouched'));
    }

    public function test_steps_follow_the_source()
    {
        $bangga = Outreach::factory()
            ->for(OutreachSource::factory()->create(['code' => 'BANGGA_SURABAYA']), 'source')
            ->create();

        $this->actingAs($bangga->petugas)
            ->get(route('outreaches.edit', $bangga))
            ->assertInertia(fn (Assert $page) => $page
                ->has('steps', 7)
                ->where('steps.5.key', 'lampiran')
                ->where('steps.6.key', 'review'));

        $baznas = Outreach::factory()
            ->for(OutreachSource::factory()->create(['code' => 'BAZNAS']), 'source')
            ->create();

        $this->actingAs($baznas->petugas)
            ->get(route('outreaches.edit', $baznas))
            ->assertInertia(fn (Assert $page) => $page
                ->has('steps', 10)
                ->where('steps.5.key', 'mustahik')
                ->where('steps.8.key', 'tanda_tangan'));
    }

    public function test_saving_a_step_stores_values_bumps_version_and_records_history()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->put(route('outreaches.update', $outreach), [
                'step' => 'klien',
                'edit_version' => 0,
                'next' => 1,
                'nama' => 'Warga Demo Satu',
                'nik' => 'DUMMY-NIK-001',
                'status_kependudukan' => 'WNI',
                'desil_1_10' => '9',
                'merokok' => '0',
                'bantuan_pernah_diterima' => '1',
                'bantuan_detail' => 'Bantuan demo',
                'punya_hutang' => '0',
                'besar_hutang' => '500000',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'keluarga']));

        $outreach->refresh();
        $this->assertSame('Warga Demo Satu', $outreach->nama);
        $this->assertSame(9, $outreach->desil_1_10);
        $this->assertSame('Sejahtera', $outreach->getAttribute('status_kesejahteraan'));
        $this->assertFalse($outreach->getAttribute('merokok'));
        $this->assertSame('Bantuan demo', $outreach->getAttribute('bantuan_detail'));
        $this->assertNull($outreach->getAttribute('besar_hutang'), 'Debt amount does not apply when there is no debt.');
        $this->assertNull($outreach->getAttribute('pendidikan_terakhir'));
        $this->assertSame(1, $outreach->edit_version);
        $this->assertSame(OutreachStatus::Draft, $outreach->status);

        $event = HistoryEvent::query()->where('event_type', HistoryEventType::SectionSaved)->sole();
        $this->assertSame(['step' => 'klien'], $event->metadata);
    }

    public function test_money_and_counts_keep_zero_distinct_from_empty()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)->put(route('outreaches.update', $outreach), [
            'step' => 'keluarga',
            'edit_version' => 0,
            'penghasilan_kepala_keluarga' => '0',
            'pengeluaran_sewa' => '',
        ])->assertSessionHasNoErrors();

        $outreach->refresh();
        $this->assertSame(0, $outreach->getAttribute('penghasilan_kepala_keluarga'));
        $this->assertNull($outreach->getAttribute('pengeluaran_sewa'));
    }

    public function test_invalid_values_are_rejected()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)->put(route('outreaches.update', $outreach), [
            'step' => 'rumah',
            'edit_version' => 0,
            'mck_layak' => 'BAGUS',
            'jumlah_becak' => '-1',
        ])->assertSessionHasErrors(['mck_layak', 'jumlah_becak']);

        $this->put(route('outreaches.update', $outreach), [
            'step' => 'keluarga',
            'edit_version' => 0,
            'pengeluaran_pdam' => 'seribu',
        ])->assertSessionHasErrors(['pengeluaran_pdam']);

        $this->assertSame(0, $outreach->refresh()->edit_version);
    }

    public function test_a_stale_edit_version_is_rejected_without_overwriting()
    {
        $outreach = $this->draft();
        $outreach->forceFill(['nama' => 'Versi terbaru', 'edit_version' => 6])->save();

        $this->actingAs($outreach->petugas)->put(route('outreaches.update', $outreach), [
            'step' => 'klien',
            'edit_version' => 5,
            'nama' => 'Tulisan lama',
        ])->assertSessionHasErrors(['edit_version']);

        $outreach->refresh();
        $this->assertSame('Versi terbaru', $outreach->nama);
        $this->assertSame(6, $outreach->edit_version);
        $this->assertSame(0, HistoryEvent::query()->count());
    }

    public function test_only_the_owner_can_edit_and_only_while_editable()
    {
        $outreach = $this->draft();
        $payload = ['step' => 'narasi', 'edit_version' => 0, 'harapan_klien' => 'x'];

        foreach ([User::factory()->create(), User::factory()->kepala()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('outreaches.edit', $outreach))->assertForbidden();
            $this->actingAs($user)->put(route('outreaches.update', $outreach), $payload)->assertForbidden();
        }

        $outreach->forceFill(['status' => OutreachStatus::MenungguPengesahan])->save();

        $this->actingAs($outreach->petugas)->get(route('outreaches.edit', $outreach))->assertForbidden();
        $this->actingAs($outreach->petugas)->put(route('outreaches.update', $outreach), $payload)->assertForbidden();
        $this->assertNull($outreach->refresh()->getAttribute('harapan_klien'));
    }

    public function test_step_completion_reflects_required_fields()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)->put(route('outreaches.update', $outreach), [
            'step' => 'narasi',
            'edit_version' => 0,
            'upaya_dilakukan' => 'Upaya demo',
        ]);

        $this->get(route('outreaches.edit', $outreach))
            ->assertInertia(fn (Assert $page) => $page->where('steps.4.state', 'incomplete'));

        $this->put(route('outreaches.update', $outreach), [
            'step' => 'narasi',
            'edit_version' => 1,
            'upaya_dilakukan' => 'Upaya demo',
            'harapan_klien' => 'Harapan demo',
            'uraian_alasan_intervensi' => 'Alasan demo',
        ]);

        $this->get(route('outreaches.edit', $outreach))
            ->assertInertia(fn (Assert $page) => $page->where('steps.4.state', 'complete'));
    }
}
