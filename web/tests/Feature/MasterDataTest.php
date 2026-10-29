<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    private function source(string $code): OutreachSource
    {
        return OutreachSource::query()->where('code', $code)->firstOrFail();
    }

    private function type(string $code): OutreachType
    {
        return OutreachType::query()->where('code', $code)->firstOrFail();
    }

    public function test_only_admin_manages_master_data()
    {
        foreach ([User::factory()->create(), User::factory()->kepala()->create()] as $user) {
            $this->actingAs($user)->get(route('master-data.index'))->assertForbidden();
            $this->actingAs($user)->post(route('master-data.types.store'), ['code' => 'X', 'name' => 'X'])->assertForbidden();
            $this->actingAs($user)->put(route('master-data.sources.update', $this->source('CSR')), ['name' => 'X', 'active' => false])->assertForbidden();
        }

        $this->actingAs($this->admin)->get(route('master-data.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('master/Index')
                ->has('sources', 3)
                ->has('types', 5));
    }

    public function test_admin_adds_a_type_with_a_unique_code()
    {
        $this->actingAs($this->admin)->post(route('master-data.types.store'), ['code' => ' bedah_rumah ', 'name' => 'Bedah Rumah'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('master-data.index'));

        $type = $this->type('BEDAH_RUMAH');
        $this->assertTrue($type->active);
        $this->assertSame('MASTER_TYPE_CREATED', AuditLog::query()->sole()->action);

        $this->actingAs($this->admin)->post(route('master-data.types.store'), ['code' => 'BEDAH_RUMAH', 'name' => 'Lagi'])->assertSessionHasErrors('code');
        $this->actingAs($this->admin)->post(route('master-data.types.store'), ['code' => '1-SALAH', 'name' => 'Salah'])->assertSessionHasErrors('code');
        $this->actingAs($this->admin)->post(route('master-data.types.store'), ['code' => 'TANPA_NAMA', 'name' => ''])->assertSessionHasErrors('name');

        // The new Type is offered when creating an Outreach.
        $this->actingAs(User::factory()->create())->get(route('outreaches.create'))
            ->assertInertia(fn (Assert $page) => $page->where('types', fn ($types) => collect($types)->contains('name', 'Bedah Rumah')));
    }

    public function test_sources_cannot_be_added()
    {
        $this->assertFalse($this->admin->can('create', OutreachSource::class));
    }

    public function test_admin_renames_and_deactivates_without_touching_the_code()
    {
        $csr = $this->source('CSR');

        $this->actingAs($this->admin)->put(route('master-data.sources.update', $csr), [
            'code' => 'DIUBAH', 'name' => 'CSR Perusahaan', 'active' => false,
        ])->assertSessionHasNoErrors();

        $csr->refresh();
        $this->assertSame('CSR', $csr->code, 'The code never changes (D-040).');
        $this->assertSame('CSR Perusahaan', $csr->name);
        $this->assertFalse($csr->active);

        $audit = AuditLog::query()->sole();
        $this->assertSame('MASTER_SOURCE_UPDATED', $audit->action);
        $this->assertEquals(['name' => 'CSR', 'active' => true], $audit->before_data);

        // Inactive Sources are no longer offered for new Outreach.
        $this->actingAs(User::factory()->create())->get(route('outreaches.create'))
            ->assertInertia(fn (Assert $page) => $page->where('sources', fn ($sources) => ! collect($sources)->contains('name', 'CSR Perusahaan')));
    }

    public function test_used_items_stay_and_show_their_usage()
    {
        $type = $this->type('RUTILAHU');
        Outreach::factory()->count(2)->create(['type_id' => $type->id]);

        $this->actingAs($this->admin)->put(route('master-data.types.update', $type), ['name' => 'Rutilahu', 'active' => false])->assertSessionHasNoErrors();

        $this->assertSame(2, Outreach::query()->where('type_id', $type->id)->count());
        $this->actingAs($this->admin)->get(route('master-data.index'))
            ->assertInertia(fn (Assert $page) => $page->where('types', fn ($types) => collect($types)->firstWhere('code', 'RUTILAHU')['used'] === 2));
    }

    public function test_at_least_one_source_and_one_type_stay_active()
    {
        foreach (['BAZNAS', 'CSR'] as $code) {
            $this->actingAs($this->admin)->put(route('master-data.sources.update', $this->source($code)), ['name' => $code, 'active' => false])->assertSessionHasNoErrors();
        }

        $this->actingAs($this->admin)->put(route('master-data.sources.update', $this->source('BANGGA_SURABAYA')), ['name' => 'Bangga Surabaya', 'active' => false])
            ->assertSessionHasErrors(['active' => 'Minimal satu Source harus tetap aktif.']);
        $this->assertTrue($this->source('BANGGA_SURABAYA')->active);

        // Saving the same values is not a change and is not audited.
        $this->actingAs($this->admin)->put(route('master-data.sources.update', $this->source('BANGGA_SURABAYA')), ['name' => 'Bangga Surabaya', 'active' => true])->assertSessionHasNoErrors();
        $this->assertSame(2, AuditLog::query()->count());
    }
}
