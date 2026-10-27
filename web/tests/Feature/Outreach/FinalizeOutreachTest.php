<?php

namespace Tests\Feature\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\InAppNotification;
use App\Models\KifayahForm;
use App\Models\MustahikForm;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\OutreachVersion;
use App\Models\User;
use App\Support\OutreachSnapshot;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class FinalizeOutreachTest extends TestCase
{
    use BuildsReadyOutreach;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
    }

    private function finalize(Outreach $outreach, ?int $version = null)
    {
        return $this->actingAs($outreach->petugas)->post(route('outreaches.finalize', $outreach), [
            'edit_version' => $version ?? $outreach->refresh()->edit_version,
        ]);
    }

    public function test_finalisasi_creates_a_numbered_version_and_submits_for_approval()
    {
        $kepala = User::factory()->kepala()->create();
        User::factory()->kepala()->inactive()->create();
        $outreach = $this->ready();

        $this->finalize($outreach)->assertSessionHasNoErrors()->assertRedirect(route('outreaches.index'));

        $outreach->refresh();
        $this->assertSame(OutreachStatus::MenungguPengesahan, $outreach->status);
        $this->assertSame('OUT-2026-00001', $outreach->outreach_number);

        $version = OutreachVersion::query()->sole();
        $this->assertSame(1, $version->version_number);
        $this->assertSame('PENDING_APPROVAL', $version->status);
        $this->assertSame($outreach->petugas_id, $version->created_by);
        $this->assertSame('PACKAGE_BAZNAS_CSR', $version->documentTemplate()->value('template_key'));
        $this->assertSame(MustahikForm::query()->sole()->signature_id, $version->mustahik_signature_id);
        $this->assertSame(KifayahForm::query()->sole()->signature_id, $version->kifayah_signature_id);
        $this->assertSame(4, $version->attachments()->count());

        $snapshot = $version->snapshot_data;
        $this->assertSame(1, $snapshot['schema_version']);
        // JSON object keys are unordered once stored in MySQL.
        $this->assertEqualsCanonicalizing(['schema_version', 'meta', 'client', 'family_income', 'expenses', 'house', 'assets', 'narrative', 'mustahik', 'kifayah'], array_keys($snapshot));
        $this->assertSame('OUT-2026-00001', $snapshot['meta']['outreach_number']);
        $this->assertSame(1, $snapshot['meta']['version_number']);
        $this->assertEquals(['desil' => 9, 'label' => 'Sejahtera'], $snapshot['client']['status_kesejahteraan']);
        $this->assertSame(0, $snapshot['expenses']['pengeluaran_pdam'], '0 stays 0.');
        $this->assertEquals(['option' => '1', 'label' => 'sangat Kecil (<4x4)'], $snapshot['mustahik']['answers']['HOUSE_BUILDING_AREA']);
        $this->assertEquals(['option' => '2', 'label' => 'Memiliki Hutang'], $snapshot['kifayah']['answers']['DEBT']);
        $this->assertArrayNotHasKey('umur', $snapshot['client'], 'Derived values are not stored.');

        $event = HistoryEvent::query()->where('event_type', HistoryEventType::Finalized)->sole();
        $this->assertSame($version->id, $event->outreach_version_id);

        $notification = InAppNotification::query()->sole();
        $this->assertSame($kepala->id, $notification->user_id, 'Only active Kepala are notified.');
        $this->assertSame('OUTREACH_FINALIZED', $notification->type);
        $this->assertSame($version->id, $notification->outreach_version_id);
    }

    public function test_numbers_follow_the_year_of_the_outreach_date()
    {
        $this->finalize($this->ready(date: '2026-03-01'));
        $second = $this->ready(date: '2026-10-27');
        $this->finalize($second);
        $other = $this->ready(date: '2025-12-31');
        $this->finalize($other);

        $this->assertSame('OUT-2026-00002', $second->refresh()->outreach_number);
        $this->assertSame('OUT-2025-00001', $other->refresh()->outreach_number);
    }

    public function test_bangga_surabaya_needs_no_forms_or_signatures()
    {
        $outreach = $this->ready('BANGGA_SURABAYA');

        $this->finalize($outreach)->assertSessionHasNoErrors();

        $version = OutreachVersion::query()->sole();
        $this->assertNull($version->mustahik_signature_id);
        $this->assertNull($version->snapshot_data['mustahik']);
        $this->assertSame('PACKAGE_BANGGA_SURABAYA', $version->documentTemplate()->value('template_key'));
    }

    public function test_incomplete_outreach_is_rejected_without_side_effects()
    {
        $outreach = $this->ready();
        OutreachAttachment::query()->where('category', 'KTP')->delete();

        $this->finalize($outreach)->assertSessionHasErrors('finalize');

        $this->assertSame(OutreachStatus::Draft, $outreach->refresh()->status);
        $this->assertNull($outreach->outreach_number);
        $this->assertSame(0, OutreachVersion::query()->count());
        $this->assertSame(0, InAppNotification::query()->count());
    }

    public function test_a_stale_version_or_a_second_submit_is_rejected()
    {
        $outreach = $this->ready();

        $this->finalize($outreach, 99)->assertSessionHasErrors('edit_version');
        $this->assertSame(0, OutreachVersion::query()->count());

        $version = $outreach->refresh()->edit_version;
        $this->finalize($outreach, $version)->assertSessionHasNoErrors();
        $this->finalize($outreach, $version)->assertForbidden();
        $this->assertSame(1, OutreachVersion::query()->count());
    }

    public function test_finalisasi_ulang_after_return_creates_the_next_version_with_the_same_number()
    {
        $outreach = $this->ready();
        $this->finalize($outreach);
        $outreach->refresh()->forceFill(['status' => OutreachStatus::PerluPerbaikan])->save();

        $this->finalize($outreach)->assertSessionHasNoErrors();

        $this->assertSame('OUT-2026-00001', $outreach->refresh()->outreach_number);
        $this->assertSame([1, 2], OutreachVersion::query()->orderBy('version_number')->pluck('version_number')->all());
        $this->assertSame(1, HistoryEvent::query()->where('event_type', HistoryEventType::Refinalized)->count());
    }

    public function test_versions_are_immutable_and_unaffected_by_later_changes()
    {
        $outreach = $this->ready();
        $this->finalize($outreach);
        $version = OutreachVersion::query()->sole();
        $before = $version->snapshot_data;

        $outreach->refresh()->forceFill(['status' => OutreachStatus::PerluPerbaikan, 'nama' => 'Nama Baru'])->save();
        OutreachAttachment::query()->where('category', 'KK')->delete();

        $version->refresh();
        $this->assertSame($before, $version->snapshot_data);
        $this->assertSame('Warga Demo', $version->snapshot_data['client']['nama']);
        $this->assertSame(4, $version->attachments()->count());

        $this->expectException(LogicException::class);
        $version->forceFill(['snapshot_data' => []])->save();
    }

    public function test_only_the_owner_may_finalize()
    {
        $outreach = $this->ready();

        foreach ([User::factory()->create(), User::factory()->kepala()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->post(route('outreaches.finalize', $outreach), ['edit_version' => $outreach->edit_version])->assertForbidden();
        }

        $this->assertSame(0, OutreachVersion::query()->count());
    }

    public function test_the_draft_preview_and_the_snapshot_share_one_structure()
    {
        $outreach = $this->ready();

        $snapshot = OutreachSnapshot::build($outreach);

        $this->assertNull($snapshot['meta']['version_number']);
        $this->assertSame('Warga Demo', $snapshot['client']['nama']);
    }
}
