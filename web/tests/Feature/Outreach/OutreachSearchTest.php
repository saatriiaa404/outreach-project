<?php

namespace Tests\Feature\Outreach;

use App\Enums\OutreachStatus;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\User;
use App\Support\OutreachSearch;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OutreachSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
    }

    private function search(User $user, array $criteria)
    {
        return $this->actingAs($user)->post(route('outreaches.search'), $criteria);
    }

    private function names(User $user): array
    {
        $names = [];
        $this->actingAs($user)->get(route('outreaches.index', ['status' => '']))
            ->assertInertia(function (Assert $page) use (&$names) {
                $names = collect($page->toArray()['props']['outreaches']['data'])->pluck('nama')->all();
            });

        return $names;
    }

    public function test_the_nik_stays_out_of_the_url()
    {
        $petugas = User::factory()->create();

        $response = $this->search($petugas, ['nik' => 'DUMMY-NIK-777']);

        $response->assertRedirect();
        $this->assertStringNotContainsString('DUMMY-NIK-777', (string) $response->headers->get('Location'));
        $this->assertSame(['nik' => 'DUMMY-NIK-777'], session(OutreachSearch::SESSION_KEY));
    }

    public function test_criteria_narrow_the_list_within_the_users_scope()
    {
        $petugas = User::factory()->create();
        $baznas = OutreachSource::query()->where('code', 'BAZNAS')->value('id');
        Outreach::factory()->for($petugas, 'petugas')->create(['nama' => 'Siti Contoh', 'nik' => 'DUMMY-NIK-001', 'source_id' => $baznas, 'outreach_date' => '2026-10-01']);
        Outreach::factory()->for($petugas, 'petugas')->create(['nama' => 'Budi Contoh', 'nik' => 'DUMMY-NIK-002', 'outreach_date' => '2026-10-20']);
        Outreach::factory()->create(['nama' => 'Siti Orang Lain', 'nik' => 'DUMMY-NIK-003']);

        $this->search($petugas, ['nama' => 'siti']);
        $this->assertSame(['Siti Contoh'], $this->names($petugas), 'Another Petugas\'s Outreach never matches.');

        $this->search($petugas, ['nik' => 'DUMMY-NIK-00']);
        $this->assertSame([], $this->names($petugas), 'NIK matches exactly.');

        $this->search($petugas, ['nik' => 'DUMMY-NIK-002']);
        $this->assertSame(['Budi Contoh'], $this->names($petugas));

        $this->search($petugas, ['source_id' => $baznas, 'date_from' => '2026-09-30', 'date_to' => '2026-10-05']);
        $this->assertSame(['Siti Contoh'], $this->names($petugas));

        $this->search($petugas, ['date_from' => '2026-10-20', 'date_to' => '2026-10-01'])->assertSessionHasErrors('date_to');

        $this->actingAs($petugas)->delete(route('outreaches.search.clear'));
        $this->assertCount(2, $this->names($petugas));
    }

    public function test_kepala_content_search_ignores_what_only_the_status_may_show()
    {
        $kepala = User::factory()->kepala()->create();
        Outreach::factory()->status(OutreachStatus::PerluPerbaikan)->create(['nama' => 'Rahasia Revisi', 'outreach_number' => 'OUT-2026-00009']);
        Outreach::factory()->status(OutreachStatus::Selesai)->create(['nama' => 'Rahasia Selesai']);
        Outreach::factory()->create(['nama' => 'Rahasia Draft']);

        $this->search($kepala, ['nama' => 'Rahasia']);
        $this->assertSame(['Rahasia Selesai'], $this->names($kepala), 'Drafts are hidden and PERLU_PERBAIKAN content is not searchable (D-039).');

        // The internal ID is not content, so it still finds the row, without its name.
        $this->search($kepala, ['code' => 'OUT-2026-00009']);
        $this->assertSame([null], $this->names($kepala));
    }
}
