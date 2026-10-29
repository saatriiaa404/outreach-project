<?php

namespace Tests\Feature\Outreach;

use App\Actions\Outreach\FinalizeOutreach;
use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\InAppNotification;
use App\Models\Outreach;
use App\Models\OutreachApproval;
use App\Models\OutreachVersion;
use App\Models\SignatureTemplate;
use App\Models\User;
use App\Support\OutreachPdf;
use Database\Seeders\DemoSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class ApproveOutreachTest extends TestCase
{
    use BuildsReadyOutreach;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
    }

    private function kepala(): User
    {
        $kepala = User::factory()->kepala()->create(['name' => 'KEPALA SINTETIS, S.Sos.', 'nip' => '000000000000000001']);
        DemoSeeder::signatureTemplate($kepala);

        return $kepala;
    }

    private function submitted(string $sourceCode = 'BAZNAS', string $date = '2026-10-27'): Outreach
    {
        $outreach = $this->ready($sourceCode, $date);
        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);

        return $outreach->refresh();
    }

    private function approveAs(User $user, Outreach $outreach, ?int $versionId = null)
    {
        return $this->actingAs($user)->post(route('outreaches.approve', $outreach), [
            'outreach_version_id' => $versionId ?? $outreach->latestVersion()->value('id'),
        ]);
    }

    private function assertNothingDecided(Outreach $outreach): void
    {
        $this->assertSame(OutreachStatus::MenungguPengesahan, $outreach->refresh()->status);
        $this->assertSame(OutreachVersion::PENDING_APPROVAL, $outreach->latestVersion()->value('status'));
        $this->assertSame(0, OutreachApproval::query()->count());
        $this->assertSame(0, HistoryEvent::query()->where('event_type', HistoryEventType::Approved)->count());
        $this->assertSame(0, InAppNotification::query()->where('type', InAppNotification::OUTREACH_COMPLETED)->count());
    }

    public function test_kepala_approves_after_every_required_pdf_renders()
    {
        $this->travelTo('2026-10-29 09:30');
        $kepala = $this->kepala();
        $outreach = $this->submitted();

        $this->approveAs($kepala, $outreach)->assertSessionHasNoErrors()->assertRedirect(route('outreaches.show', $outreach));

        $this->assertSame(OutreachStatus::Selesai, $outreach->refresh()->status);
        $version = OutreachVersion::query()->sole();
        $this->assertSame(OutreachVersion::APPROVED, $version->status);

        $approval = OutreachApproval::query()->sole();
        $this->assertSame(OutreachApproval::APPROVED, $approval->decision);
        $this->assertSame($kepala->id, $approval->kepala_id);
        $this->assertNull($approval->note);
        $this->assertSame(SignatureTemplate::activeFor($kepala)?->id, $approval->head_signature_template_id);
        $this->assertNull($approval->stamp_template_id, 'Digital stamp use is pending validation.');
        $this->assertSame('2026-10-29', $approval->decided_at->toDateString());

        $event = HistoryEvent::query()->where('event_type', HistoryEventType::Approved)->sole();
        $this->assertSame($approval->id, $event->approval_id);
        $this->assertSame($version->id, $event->outreach_version_id);

        $notification = InAppNotification::query()->where('type', InAppNotification::OUTREACH_COMPLETED)->sole();
        $this->assertSame($outreach->petugas_id, $notification->user_id);
        $this->assertSame($approval->id, $notification->approval_id);
    }

    public function test_the_approval_block_prints_the_stored_date_name_nip_and_template()
    {
        $this->travelTo('2026-10-29 09:30');
        $kepala = $this->kepala();
        $outreach = $this->submitted();
        $this->approveAs($kepala, $outreach);
        $this->travelTo('2026-11-05 10:00');

        $pdf = app(OutreachPdf::class);
        $html = $pdf->html(OutreachVersion::query()->sole(), 'outreach', $pdf->approvalBlock(OutreachApproval::query()->sole()));

        $this->assertStringContainsString('Surabaya, 29 Oktober 2026', $html, 'The approval date, not the render date.');
        $this->assertStringContainsString('KEPALA SINTETIS, S.Sos.', $html);
        $this->assertStringContainsString('NIP. 000000000000000001', $html);
        $this->assertSame(5, substr_count($html, 'src="data:image/'), 'Four attachments and the Kepala signature.');
    }

    public function test_a_failed_pdf_fails_the_whole_approval()
    {
        $kepala = $this->kepala();
        $outreach = $this->submitted();
        $this->partialMock(OutreachPdf::class, fn ($mock) => $mock->shouldReceive('render')->andReturnUsing(
            fn (OutreachVersion $version, string $document) => $document === 'kifayah' ? throw new RuntimeException('Dompdf crashed') : '%PDF-1.7',
        ));

        $this->approveAs($kepala, $outreach)->assertSessionHasErrors(['approve' => 'Pengesahan gagal: PDF Kifayah tidak dapat dibuat. Status tetap Menunggu Pengesahan; coba lagi setelah masalah diperbaiki.']);

        $this->assertNothingDecided($outreach);
    }

    public function test_a_missing_attachment_file_blocks_approval_and_a_retry_succeeds()
    {
        $kepala = $this->kepala();
        $outreach = $this->submitted();
        $file = OutreachVersion::query()->sole()->attachments()->first()->file;
        $bytes = Storage::disk('local')->get($file->storage_key);
        Storage::disk('local')->delete($file->storage_key);

        $this->approveAs($kepala, $outreach)->assertSessionHasErrors(['approve']);
        $this->assertNothingDecided($outreach);

        Storage::disk('local')->put($file->storage_key, (string) $bytes);
        $this->approveAs($kepala, $outreach)->assertSessionHasNoErrors();
        $this->assertSame(OutreachStatus::Selesai, $outreach->refresh()->status);
    }

    public function test_bangga_surabaya_needs_only_the_outreach_pdf()
    {
        $rendered = [];
        $this->partialMock(OutreachPdf::class, function ($mock) use (&$rendered) {
            $mock->shouldReceive('render')->andReturnUsing(function (OutreachVersion $version, string $document) use (&$rendered) {
                $rendered[] = $document;

                return '%PDF-1.7';
            });
        });

        $this->approveAs($this->kepala(), $this->submitted('BANGGA_SURABAYA'))->assertSessionHasNoErrors();

        $this->assertSame(['outreach'], $rendered);
    }

    public function test_a_kepala_without_nip_or_signature_template_cannot_approve()
    {
        $outreach = $this->submitted();

        $withoutNip = User::factory()->kepala()->create(['nip' => null]);
        DemoSeeder::signatureTemplate($withoutNip);
        $this->approveAs($withoutNip, $outreach)->assertSessionHasErrors(['approve' => 'NIP Kepala belum diisi. Minta Admin melengkapi NIP sebelum mengesahkan.']);

        $withoutTemplate = User::factory()->kepala()->create();
        $this->approveAs($withoutTemplate, $outreach)->assertSessionHasErrors(['approve' => 'Template tanda tangan Kepala belum tersedia. Minta Admin menyiapkannya sebelum mengesahkan.']);

        $retired = $this->kepala();
        SignatureTemplate::query()->where('head_user_id', $retired->id)->update(['is_active' => false]);
        $this->approveAs($retired, $outreach)->assertSessionHasErrors('approve');

        $this->assertNothingDecided($outreach);
    }

    public function test_only_kepala_may_approve_the_latest_pending_version_once()
    {
        $outreach = $this->submitted();

        foreach ([$outreach->petugas, User::factory()->create(), User::factory()->admin()->create()] as $user) {
            $this->approveAs($user, $outreach)->assertForbidden();
        }

        $kepala = $this->kepala();
        $this->approveAs($kepala, $outreach, 999)->assertSessionHasErrors('approve');
        $this->assertNothingDecided($outreach);

        $this->approveAs($kepala, $outreach)->assertSessionHasNoErrors();
        $this->approveAs($kepala, $outreach->refresh())->assertForbidden();
        $this->assertSame(1, OutreachApproval::query()->count());
    }

    public function test_a_failed_notification_does_not_undo_the_approval()
    {
        $kepala = $this->kepala();
        $outreach = $this->submitted();
        InAppNotification::creating(fn (InAppNotification $notification) => $notification->type === InAppNotification::OUTREACH_COMPLETED
            ? throw new RuntimeException('Notification store down')
            : null);

        $this->approveAs($kepala, $outreach)->assertSessionHasNoErrors();

        $this->assertSame(OutreachStatus::Selesai, $outreach->refresh()->status);
        $this->assertSame(1, OutreachApproval::query()->count());
    }

    public function test_the_review_page_offers_approve_only_while_pending()
    {
        $kepala = $this->kepala();
        $outreach = $this->submitted();

        $this->actingAs($kepala)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page->where('canApprove', true)->where('canReturn', true));

        $this->approveAs($kepala, $outreach);

        $this->actingAs($kepala)->get(route('outreaches.show', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->where('outreach.status', 'SELESAI')
                ->where('canApprove', false)
                ->where('canReturn', false)
                ->where('version.status', 'APPROVED')
                ->where('history.1.type', 'APPROVED'));
    }
}
