<?php

namespace Tests\Feature\Outreach;

use App\Enums\OutreachStatus;
use App\Enums\SpecialUnlockRequestStatus;
use App\Models\Outreach;
use App\Models\SpecialUnlockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SpecialUnlockRequestTest extends TestCase
{
    use RefreshDatabase;

    private function outreach(OutreachStatus $status = OutreachStatus::Selesai): Outreach
    {
        return Outreach::factory()->status($status)->create(['outreach_number' => 'OUT-2026-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT)]);
    }

    private function ask(User $user, Outreach $outreach, string $reason = 'NIK pada data klien salah ketik.')
    {
        return $this->actingAs($user)->post(route('outreaches.special-unlock-requests.store', $outreach), ['reason' => $reason]);
    }

    private function pending(Outreach $outreach): SpecialUnlockRequest
    {
        $this->ask($outreach->petugas, $outreach)->assertSessionHasNoErrors();

        return SpecialUnlockRequest::query()->latest('id')->firstOrFail();
    }

    public function test_the_owning_petugas_and_the_kepala_may_request_on_submitted_or_completed_outreach()
    {
        $kepala = User::factory()->kepala()->create();

        foreach ([OutreachStatus::MenungguPengesahan, OutreachStatus::Selesai] as $status) {
            $outreach = $this->outreach($status);
            $this->ask($outreach->petugas, $outreach)->assertSessionHasNoErrors()->assertRedirect(route('outreaches.show', $outreach));

            $request = SpecialUnlockRequest::query()->where('outreach_id', $outreach->id)->sole();
            $this->assertSame(SpecialUnlockRequestStatus::Pending, $request->status);
            $this->assertSame($outreach->petugas_id, $request->requester_id);
            $this->assertSame('PETUGAS', $request->requester_role_snapshot->value);
            $this->assertSame($status, $outreach->refresh()->status, 'A request never changes the Outreach status.');
        }

        $outreach = $this->outreach();
        $this->ask($kepala, $outreach)->assertSessionHasNoErrors();
        $this->assertSame('KEPALA', SpecialUnlockRequest::query()->where('outreach_id', $outreach->id)->sole()->requester_role_snapshot->value);
    }

    public function test_others_and_editable_outreach_cannot_be_requested()
    {
        $outreach = $this->outreach();

        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
            $this->ask($user, $outreach)->assertForbidden();
        }

        foreach ([OutreachStatus::Draft, OutreachStatus::PerluPerbaikan] as $status) {
            $editable = $this->outreach($status);
            $this->ask($editable->petugas, $editable)->assertForbidden();
        }

        $this->assertSame(0, SpecialUnlockRequest::query()->count());
    }

    public function test_a_reason_is_required()
    {
        $outreach = $this->outreach();

        $this->ask($outreach->petugas, $outreach, '  ')->assertSessionHasErrors('reason');
        $this->assertSame(0, SpecialUnlockRequest::query()->count());
    }

    public function test_only_one_open_request_per_outreach()
    {
        $kepala = User::factory()->kepala()->create();
        $admin = User::factory()->admin()->create();
        $outreach = $this->outreach();
        $first = $this->pending($outreach);

        $this->ask($kepala, $outreach)->assertSessionHasErrors(['special_unlock' => 'Sudah ada permintaan Special Unlock yang masih terbuka untuk Outreach ini.']);
        $this->ask($outreach->petugas, $outreach)->assertSessionHasErrors('special_unlock');

        $this->actingAs($kepala)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->where('specialUnlock.canRequest', false)
                ->where('specialUnlock.openBy', $outreach->petugas->name)
                ->has('specialUnlock.requests', 0));

        // Still open while processing; a closed request frees the Outreach.
        $this->actingAs($admin)->post(route('special-unlock-requests.process', $first));
        $this->ask($kepala, $outreach)->assertSessionHasErrors('special_unlock');
        $this->actingAs($admin)->post(route('special-unlock-requests.reject', $first), ['processed_reason' => 'Data sudah benar.']);
        $this->ask($kepala, $outreach)->assertSessionHasNoErrors();
        $this->assertSame(2, SpecialUnlockRequest::query()->count());
    }

    public function test_the_requester_cancels_only_while_pending()
    {
        $admin = User::factory()->admin()->create();
        $outreach = $this->outreach();
        $request = $this->pending($outreach);

        $this->actingAs(User::factory()->kepala()->create())->post(route('special-unlock-requests.cancel', $request))->assertForbidden();
        $this->actingAs($outreach->petugas)->post(route('special-unlock-requests.cancel', $request))->assertSessionHasNoErrors();
        $this->assertSame(SpecialUnlockRequestStatus::Cancelled, $request->refresh()->status);

        $second = $this->pending($outreach);
        $this->actingAs($admin)->post(route('special-unlock-requests.process', $second))->assertSessionHasNoErrors();
        $this->actingAs($outreach->petugas)->post(route('special-unlock-requests.cancel', $second))
            ->assertSessionHasErrors(['special_unlock' => 'Permintaan tidak dapat dibatalkan karena sudah diproses Admin.']);
        $this->assertSame(SpecialUnlockRequestStatus::Processing, $second->refresh()->status);
    }

    public function test_admin_processes_then_rejects_with_a_reason_the_requester_can_see()
    {
        $admin = User::factory()->admin()->create();
        $outreach = $this->outreach();
        $request = $this->pending($outreach);

        // Rejecting needs PROCESSING first (31.4).
        $this->actingAs($admin)->post(route('special-unlock-requests.reject', $request), ['processed_reason' => 'Tidak perlu.'])->assertSessionHasErrors('special_unlock');

        $this->actingAs($admin)->post(route('special-unlock-requests.process', $request))->assertSessionHasNoErrors();
        $request->refresh();
        $this->assertSame(SpecialUnlockRequestStatus::Processing, $request->status);
        $this->assertSame($admin->id, $request->admin_id);
        $this->actingAs($admin)->post(route('special-unlock-requests.process', $request))->assertSessionHasErrors('special_unlock');

        $this->actingAs($admin)->post(route('special-unlock-requests.reject', $request), ['processed_reason' => ''])->assertSessionHasErrors('processed_reason');
        $this->actingAs($admin)->post(route('special-unlock-requests.reject', $request), ['processed_reason' => 'NIK sudah sesuai KTP.'])->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(SpecialUnlockRequestStatus::Rejected, $request->status);
        $this->assertSame('NIK sudah sesuai KTP.', $request->processed_reason);
        $this->assertNotNull($request->processed_at);
        $this->assertSame(OutreachStatus::Selesai, $outreach->refresh()->status, 'Rejection never changes the Outreach.');

        $this->actingAs($outreach->petugas)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->where('specialUnlock.canRequest', true)
                ->where('specialUnlock.requests.0.status', 'REJECTED')
                ->where('specialUnlock.requests.0.processed_reason', 'NIK sudah sesuai KTP.'));
    }

    public function test_only_admin_works_the_queue()
    {
        $outreach = $this->outreach();
        $request = $this->pending($outreach);

        foreach ([$outreach->petugas, User::factory()->kepala()->create()] as $user) {
            $this->actingAs($user)->get(route('special-unlock-requests.index'))->assertForbidden();
            $this->actingAs($user)->post(route('special-unlock-requests.process', $request))->assertForbidden();
            $this->actingAs($user)->post(route('special-unlock-requests.reject', $request), ['processed_reason' => 'x'])->assertForbidden();
        }

        $this->assertSame(SpecialUnlockRequestStatus::Pending, $request->refresh()->status);
    }

    public function test_the_queue_shows_open_requests_oldest_first_by_default()
    {
        $admin = User::factory()->admin()->create();
        $this->travelTo('2026-10-29 08:00');
        $older = $this->pending($this->outreach());
        $this->travelTo('2026-10-29 09:00');
        $newer = $this->pending($this->outreach(OutreachStatus::MenungguPengesahan));
        $cancelled = $this->pending($this->outreach());
        $this->actingAs($cancelled->requester)->post(route('special-unlock-requests.cancel', $cancelled));

        $this->actingAs($admin)->get(route('special-unlock-requests.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('special-unlock/Index')
                ->where('status', 'OPEN')
                ->has('requests.data', 2)
                ->where('requests.data.0.id', $older->id)
                ->where('requests.data.1.id', $newer->id)
                ->where('requests.data.1.outreach.status', 'MENUNGGU_PENGESAHAN'));

        $this->actingAs($admin)->get(route('special-unlock-requests.index', ['status' => 'CANCELLED']))
            ->assertInertia(fn (Assert $page) => $page->where('status', 'CANCELLED')->has('requests.data', 1));
        $this->actingAs($admin)->get(route('special-unlock-requests.index', ['status' => '']))
            ->assertInertia(fn (Assert $page) => $page->where('status', null)->has('requests.data', 3));
    }

    public function test_requesters_see_only_their_own_requests_and_admin_sees_all()
    {
        $admin = User::factory()->admin()->create();
        $kepala = User::factory()->kepala()->create();
        $outreach = $this->outreach();
        $request = $this->pending($outreach);
        $this->actingAs($outreach->petugas)->post(route('special-unlock-requests.cancel', $request));
        $this->ask($kepala, $outreach);

        $this->actingAs($outreach->petugas)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->has('specialUnlock.requests', 1)
                ->where('specialUnlock.requests.0.status', 'CANCELLED')
                ->where('specialUnlock.openBy', $kepala->name));
        $this->actingAs($admin)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->has('specialUnlock.requests', 2)
                ->where('specialUnlock.canRequest', false));
    }
}
