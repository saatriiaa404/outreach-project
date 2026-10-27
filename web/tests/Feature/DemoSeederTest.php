<?php

namespace Tests\Feature;

use App\Enums\OutreachStatus;
use App\Enums\UserRole;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'test-demo-password';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setDemoPassword(self::PASSWORD);
    }

    public function test_seeder_creates_master_data_demo_accounts_and_drafts()
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(['BANGGA_SURABAYA', 'BAZNAS', 'CSR'], OutreachSource::query()->orderBy('code')->pluck('code')->all());
        $this->assertSame(5, OutreachType::query()->count());

        $this->assertSame(UserRole::Petugas, User::query()->where('email', 'petugas.demo@example.test')->sole()->role);
        $kepala = User::query()->where('email', 'kepala.demo@example.test')->sole();
        $this->assertSame(UserRole::Kepala, $kepala->role);
        $this->assertMatchesRegularExpression('/^\d{18}$/', $kepala->nip);
        $this->assertSame(1, User::query()->where('role', UserRole::Kepala)->count());

        $drafts = Outreach::query()->orderBy('draft_code')->get();
        $this->assertSame(['DRAFT-DEMO00000001', 'DRAFT-DEMO00000003'], $drafts->pluck('draft_code')->all());
        $this->assertTrue($drafts->every(fn (Outreach $o) => $o->status === OutreachStatus::Draft));
        $this->assertSame(2, DB::table('history_events')->where('event_type', 'CREATED')->count());
    }

    public function test_seeder_is_idempotent()
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::query()->count());
        $this->assertSame(2, Outreach::query()->count());
        $this->assertSame(2, DB::table('history_events')->count());
    }

    public function test_demo_accounts_can_log_in_with_the_configured_password()
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('login.store'), [
            'email' => 'admin.demo@example.test',
            'password' => self::PASSWORD,
        ]);

        $this->assertAuthenticated();
    }

    public function test_seeder_refuses_to_run_without_a_demo_password()
    {
        $this->setDemoPassword(null);

        $this->expectException(RuntimeException::class);

        $this->seed(DatabaseSeeder::class);
    }

    private function setDemoPassword(?string $value): void
    {
        config(['app.demo_user_password' => $value]);
    }
}
