<?php

namespace Tests\Feature;

use App\Enums\OutreachStatus;
use App\Enums\UserRole;
use App\Models\Outreach;
use App\Models\OutreachApproval;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\OutreachVersion;
use App\Models\User;
use App\Support\OutreachPdf;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'test-demo-password';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
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
        $this->assertSame('Penata Tk. I', $kepala->pangkat);
        $this->assertSame(1, User::query()->where('role', UserRole::Kepala)->count());

        $statuses = Outreach::query()->orderBy('draft_code')->get()->mapWithKeys(fn (Outreach $o) => [$o->draft_code => $o->status]);
        $this->assertSame([
            'DRAFT-DEMO00000001' => OutreachStatus::Draft,
            'DRAFT-DEMO00000002' => OutreachStatus::MenungguPengesahan,
            'DRAFT-DEMO00000003' => OutreachStatus::Draft,
            'DRAFT-DEMO00000004' => OutreachStatus::Selesai,
        ], $statuses->all());
        $this->assertSame(4, DB::table('history_events')->where('event_type', 'CREATED')->count());
    }

    public function test_demo_002_waits_for_the_kepala_and_demo_004_is_approved_with_its_pdfs()
    {
        $this->seed(DatabaseSeeder::class);

        $pending = Outreach::query()->where('draft_code', 'DRAFT-DEMO00000002')->sole();
        $this->assertSame('CSR', $pending->source->code);
        $this->assertSame(OutreachVersion::PENDING_APPROVAL, $pending->latestVersion()->value('status'));
        $this->assertSame(4, $pending->attachments()->count());

        $completed = Outreach::query()->where('draft_code', 'DRAFT-DEMO00000004')->sole();
        $this->assertSame(OutreachVersion::APPROVED, $completed->latestVersion()->value('status'));
        $approval = OutreachApproval::query()->where('outreach_id', $completed->id)->sole();
        $this->assertSame(OutreachApproval::APPROVED, $approval->decision);
        $this->assertNotNull($completed->outreach_number, 'Finalisasi gave it its number.');

        $version = OutreachVersion::query()->where('outreach_id', $completed->id)->sole();
        $pdf = app(OutreachPdf::class);
        $this->assertSame(['outreach', 'mustahik', 'kifayah'], $pdf->documents($version));
        $this->assertStringStartsWith('%PDF-', $pdf->render($version, 'outreach', $pdf->approvalBlock($approval)));
    }

    public function test_seeder_is_idempotent()
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [DB::table('history_events')->count(), OutreachVersion::query()->count(), OutreachApproval::query()->count()];
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::query()->count());
        $this->assertSame(4, Outreach::query()->count());
        $this->assertSame($counts, [DB::table('history_events')->count(), OutreachVersion::query()->count(), OutreachApproval::query()->count()]);
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
