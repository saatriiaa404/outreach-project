<?php

namespace Tests\Feature\Outreach;

use App\Models\Outreach;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mustahik and Kifayah shown as documents from the PDF templates (D-069).
 */
class FormPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['app.demo_user_password' => 'test-demo-password']);
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->sole();
    }

    public function test_the_kepala_sees_both_forms_of_the_queued_version_as_documents()
    {
        $outreach = Outreach::query()->where('draft_code', 'DRAFT-DEMO00000002')->sole();
        $version = $outreach->versions()->latest('version_number')->firstOrFail();
        $kepala = $this->user('kepala.demo@example.test');

        $this->actingAs($kepala)->get(route('outreaches.versions.preview', [$outreach, $version, 'mustahik']))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertSee('FORM SURVEY MUSTAHIK')
            ->assertSee('body { margin: 12mm 10mm; }', false);
        $this->actingAs($kepala)->get(route('outreaches.versions.preview', [$outreach, $version, 'kifayah']))
            ->assertOk()
            ->assertSee($version->snapshot_data['client']['nama']);
    }

    public function test_the_petugas_previews_the_draft_forms_on_review()
    {
        $empty = Outreach::query()->where('draft_code', 'DRAFT-DEMO00000001')->sole();
        $filled = Outreach::query()->where('draft_code', 'DRAFT-DEMO00000002')->sole();
        $petugas = $this->user('petugas.demo@example.test');

        $this->actingAs($petugas)->get(route('outreaches.preview', [$filled, 'mustahik']))
            ->assertOk()
            ->assertSee('FORM SURVEY MUSTAHIK');
        $this->actingAs($petugas)->get(route('outreaches.preview', [$empty, 'kifayah']))
            ->assertOk()
            ->assertSee('Formulir Had Kifayah belum diisi.');
    }

    public function test_other_users_and_sources_without_forms_get_nothing()
    {
        $outreach = Outreach::query()->where('draft_code', 'DRAFT-DEMO00000001')->sole();
        $bangga = Outreach::query()->where('draft_code', 'DRAFT-DEMO00000003')->sole();
        $petugas = $this->user('petugas.demo@example.test');

        $this->actingAs(User::factory()->create())->get(route('outreaches.preview', [$outreach, 'mustahik']))->assertForbidden();
        $this->actingAs($petugas)->get(route('outreaches.preview', [$bangga, 'kifayah']))->assertNotFound();
        $this->actingAs($petugas)->get("/outreach/{$outreach->id}/preview/outreach")->assertNotFound();
    }
}
