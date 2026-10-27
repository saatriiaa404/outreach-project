<?php

namespace Tests\Feature\Outreach;

use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutosaveOutreachTest extends TestCase
{
    use RefreshDatabase;

    private function draft(): Outreach
    {
        return Outreach::factory()->for(User::factory()->create(), 'petugas')->create();
    }

    public function test_autosave_stores_values_and_returns_the_new_version_without_history()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->putJson(route('outreaches.autosave', $outreach), [
                'step' => 'klien',
                'edit_version' => 0,
                'nama' => 'Warga Demo Autosave',
                'punya_hutang' => '0',
                'besar_hutang' => '250000',
            ])
            ->assertOk()
            ->assertJsonPath('edit_version', 1);

        $outreach->refresh();
        $this->assertSame('Warga Demo Autosave', $outreach->nama);
        $this->assertNull($outreach->getAttribute('besar_hutang'), 'Autosave uses the same normalization as Save Draft.');
        $this->assertSame(1, $outreach->edit_version);
        $this->assertSame(OutreachStatus::Draft, $outreach->status);
        $this->assertSame(0, HistoryEvent::query()->count(), 'Autosave is not recorded on every interval.');
    }

    public function test_autosave_with_a_stale_version_answers_conflict_without_overwriting()
    {
        $outreach = $this->draft();
        $outreach->forceFill(['nama' => 'Versi terbaru', 'edit_version' => 3])->save();

        $this->actingAs($outreach->petugas)
            ->putJson(route('outreaches.autosave', $outreach), [
                'step' => 'klien',
                'edit_version' => 2,
                'nama' => 'Tulisan lama',
            ])
            ->assertConflict();

        $outreach->refresh();
        $this->assertSame('Versi terbaru', $outreach->nama);
        $this->assertSame(3, $outreach->edit_version);
    }

    public function test_autosave_rejects_invalid_values()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->putJson(route('outreaches.autosave', $outreach), [
                'step' => 'keluarga',
                'edit_version' => 0,
                'pengeluaran_pdam' => 'seribu',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pengeluaran_pdam']);

        $this->assertSame(0, $outreach->refresh()->edit_version);
    }

    public function test_autosave_follows_the_same_authorization_as_save_draft()
    {
        $outreach = $this->draft();
        $payload = ['step' => 'narasi', 'edit_version' => 0, 'harapan_klien' => 'x'];

        foreach ([User::factory()->create(), User::factory()->kepala()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->putJson(route('outreaches.autosave', $outreach), $payload)->assertForbidden();
        }

        $outreach->forceFill(['status' => OutreachStatus::MenungguPengesahan])->save();

        $this->actingAs($outreach->petugas)->putJson(route('outreaches.autosave', $outreach), $payload)->assertForbidden();
        $this->assertNull($outreach->refresh()->getAttribute('harapan_klien'));
    }

    public function test_guests_can_not_autosave()
    {
        $outreach = $this->draft();

        $this->putJson(route('outreaches.autosave', $outreach), ['step' => 'narasi', 'edit_version' => 0])
            ->assertUnauthorized();
    }
}
