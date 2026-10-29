<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\DocumentTemplateVersion;
use App\Models\Outreach;
use App\Models\OutreachApproval;
use App\Models\OutreachVersion;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function create(array $data = [])
    {
        return $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Petugas Baru', 'email' => 'petugas.baru@example.test', 'role' => 'PETUGAS',
            'password' => 'kata-sandi-uji-1', 'password_confirmation' => 'kata-sandi-uji-1', 'nip' => '',
            ...$data,
        ]);
    }

    private function edit(User $user, array $data = [])
    {
        return $this->actingAs($this->admin)->put(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'status' => $user->status->value, 'nip' => $user->nip ?? '',
            ...$data,
        ]);
    }

    /**
     * An approval printed with the Kepala's NIP.
     */
    private function approvedBy(User $kepala): void
    {
        $outreach = Outreach::factory()->create();
        $version = new OutreachVersion;
        $version->forceFill([
            'outreach_id' => $outreach->id, 'version_number' => 1, 'status' => OutreachVersion::APPROVED,
            'snapshot_data' => [], 'document_template_version_id' => $this->template(), 'created_by' => $outreach->petugas_id, 'finalized_at' => now(),
        ])->save();
        (new OutreachApproval)->forceFill([
            'outreach_id' => $outreach->id, 'outreach_version_id' => $version->id, 'kepala_id' => $kepala->id,
            'decision' => OutreachApproval::APPROVED, 'decided_at' => now(),
        ])->save();
    }

    private function template(): int
    {
        $this->seed(MasterDataSeeder::class);

        return (int) DocumentTemplateVersion::query()->value('id');
    }

    public function test_only_admin_manages_accounts()
    {
        foreach ([User::factory()->create(), User::factory()->kepala()->create()] as $user) {
            $this->actingAs($user)->get(route('users.index'))->assertForbidden();
            $this->actingAs($user)->post(route('users.store'), [])->assertForbidden();
            $this->actingAs($user)->put(route('users.update', $this->admin), [])->assertForbidden();
        }

        $this->actingAs($this->admin)->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('users/Index')->where('users.0.is_self', true));
    }

    public function test_admin_creates_an_account_with_a_hashed_initial_password()
    {
        $this->create()->assertSessionHasNoErrors()->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'petugas.baru@example.test')->sole();
        $this->assertSame('PETUGAS', $user->role->value);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNull($user->nip, 'NIP is for Kepala only.');
        $this->assertTrue(Hash::check('kata-sandi-uji-1', $user->password_hash));

        $audit = AuditLog::query()->sole();
        $this->assertSame('USER_CREATED', $audit->action);
        $this->assertArrayNotHasKey('password_hash', $audit->after_data, 'Passwords are never audited.');

        $this->create()->assertSessionHasErrors('email');
        $this->create(['email' => 'lain@example.test', 'password_confirmation' => 'beda'])->assertSessionHasErrors('password');
    }

    public function test_only_one_kepala_is_active()
    {
        $kepala = User::factory()->kepala()->create(['name' => 'Kepala Lama']);

        $this->create(['role' => 'KEPALA', 'email' => 'kepala.baru@example.test', 'nip' => '000000000000000009'])
            ->assertSessionHasErrors(['status' => 'Masih ada Kepala aktif (Kepala Lama). Nonaktifkan akun tersebut terlebih dahulu.']);

        $this->edit($kepala, ['status' => 'INACTIVE'])->assertSessionHasNoErrors();
        $this->create(['role' => 'KEPALA', 'email' => 'kepala.baru@example.test', 'nip' => '000000000000000009'])->assertSessionHasNoErrors();

        // The old Kepala cannot come back while the new one is active.
        $this->edit($kepala->refresh(), ['status' => 'ACTIVE'])->assertSessionHasErrors('status');
        $this->assertSame(1, User::query()->where('role', 'KEPALA')->where('status', 'ACTIVE')->count());
    }

    public function test_the_role_never_changes_and_nip_is_only_for_kepala()
    {
        $petugas = User::factory()->create();

        $this->edit($petugas, ['role' => 'ADMIN', 'nip' => '123'])->assertSessionHasNoErrors();

        $petugas->refresh();
        $this->assertSame('PETUGAS', $petugas->role->value);
        $this->assertNull($petugas->nip);

        $kepala = User::factory()->kepala()->create();
        $this->edit($kepala, ['nip' => '12AB'])->assertSessionHasErrors('nip');
        $this->edit($kepala, ['nip' => '1234567890123456789'])->assertSessionHasErrors('nip');
    }

    public function test_a_nip_used_on_approvals_changes_only_as_an_administrative_correction()
    {
        $kepala = User::factory()->kepala()->create(['nip' => '000000000000000001']);

        // Not used yet: a normal audited update.
        $this->edit($kepala, ['nip' => '000000000000000002'])->assertSessionHasNoErrors();
        $this->assertSame('USER_UPDATED', AuditLog::query()->latest('id')->value('action'));

        $this->approvedBy($kepala);
        $this->edit($kepala->refresh(), ['nip' => '000000000000000003'])->assertSessionHasErrors('nip_correction_reason');
        $this->assertSame('000000000000000002', $kepala->refresh()->nip);

        $this->edit($kepala, ['nip' => '000000000000000003', 'nip_correction_reason' => 'Salah ketik saat input awal.'])->assertSessionHasNoErrors();
        $audit = AuditLog::query()->latest('id')->firstOrFail();
        $this->assertSame('USER_NIP_CORRECTED', $audit->action);
        $this->assertSame('Salah ketik saat input awal.', $audit->metadata['reason']);
        $this->assertSame('000000000000000002', $audit->before_data['nip']);

        // Other changes to the same Kepala need no reason.
        $this->edit($kepala->refresh(), ['name' => 'Nama Baru'])->assertSessionHasNoErrors();
    }

    public function test_admins_cannot_lock_themselves_or_the_last_admin_out()
    {
        $this->edit($this->admin, ['status' => 'INACTIVE'])->assertSessionHasErrors(['status' => 'Anda tidak dapat menonaktifkan akun sendiri.']);

        $other = User::factory()->admin()->create();
        $this->edit($other, ['status' => 'INACTIVE'])->assertSessionHasNoErrors();
        $this->assertSame(UserStatus::Inactive, $other->refresh()->status);
    }

    public function test_admin_resets_a_password_and_inactive_users_are_signed_out()
    {
        $petugas = User::factory()->create();

        $this->actingAs($this->admin)->put(route('users.password', $petugas), ['password' => 'sandi-baru-uji-2', 'password_confirmation' => 'sandi-baru-uji-2'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('sandi-baru-uji-2', $petugas->refresh()->password_hash));
        $this->assertSame('USER_PASSWORD_RESET', AuditLog::query()->sole()->action);

        $this->edit($petugas, ['status' => 'INACTIVE']);
        $this->actingAs($petugas->refresh())->get(route('outreaches.index'))->assertRedirect();
    }
}
