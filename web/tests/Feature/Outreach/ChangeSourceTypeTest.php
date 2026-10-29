<?php

namespace Tests\Feature\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChangeSourceTypeTest extends TestCase
{
    use RefreshDatabase;

    private function source(string $code): OutreachSource
    {
        return OutreachSource::factory()->create(['code' => $code, 'name' => $code]);
    }

    private function draft(string $sourceCode): Outreach
    {
        return Outreach::factory()->for($this->source($sourceCode), 'source')->create(['nama' => 'Warga Demo'])->refresh();
    }

    /**
     * Mustahik and Kifayah rows with one signature, as later phases will create them.
     */
    private function fillSourceForms(Outreach $outreach): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('signatures/demo.png', 'png');

        $fileId = DB::table('files')->insertGetId([
            'storage_disk' => 'local', 'storage_key' => 'signatures/demo.png', 'original_name' => 'ttd.png',
            'mime_type' => 'image/png', 'extension' => 'png', 'size_bytes' => 3, 'sha256_hash' => str_repeat('a', 64),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $signatureId = DB::table('signatures')->insertGetId([
            'file_id' => $fileId, 'signer_type' => 'PETUGAS', 'signer_user_id' => $outreach->petugas_id,
            'signer_name_snapshot' => 'Petugas Demo', 'signed_at' => now(), 'created_at' => now(),
        ]);
        $mustahikId = DB::table('mustahik_forms')->insertGetId([
            'outreach_id' => $outreach->id, 'petugas_id' => $outreach->petugas_id, 'signature_id' => $signatureId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('mustahik_answers')->insert(['mustahik_form_id' => $mustahikId, 'question_code' => 'Q01', 'answer' => '"A"', 'created_at' => now(), 'updated_at' => now()]);
        $kifayahId = DB::table('kifayah_forms')->insertGetId(['outreach_id' => $outreach->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('kifayah_answers')->insert(['kifayah_form_id' => $kifayahId, 'question_code' => 'K01', 'answer' => '"Ya"', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function change(Outreach $outreach, OutreachSource $source, ?OutreachType $type = null, bool $confirm = false, ?int $version = null)
    {
        return $this->actingAs($outreach->petugas)->put(route('outreaches.source-type', $outreach), [
            'source_id' => $source->id,
            'type_id' => ($type ?? $outreach->type)->id,
            'edit_version' => $version ?? $outreach->edit_version,
            'confirm_destructive' => $confirm ? '1' : '0',
        ]);
    }

    public function test_switching_between_baznas_and_csr_keeps_all_data()
    {
        $outreach = $this->draft('BAZNAS');
        $this->fillSourceForms($outreach);
        $type = OutreachType::factory()->create();

        $this->change($outreach, $this->source('CSR'), $type)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'identitas']));

        $outreach->refresh();
        $this->assertSame('CSR', $outreach->source->code);
        $this->assertSame($type->id, $outreach->type_id);
        $this->assertSame('Warga Demo', $outreach->nama);
        $this->assertSame(1, $outreach->edit_version);
        $this->assertSame(1, DB::table('mustahik_forms')->count());
        $this->assertSame(1, DB::table('signatures')->count());

        $event = HistoryEvent::query()->sole();
        $this->assertSame(HistoryEventType::SectionSaved, $event->event_type);
        $this->assertEquals(['before' => 'BAZNAS', 'after' => 'CSR'], $event->field_changes['source']);
        $this->assertFalse($event->metadata['cleared_forms']);
    }

    public function test_leaving_baznas_for_bangga_surabaya_needs_confirmation()
    {
        $outreach = $this->draft('BAZNAS');
        $this->fillSourceForms($outreach);

        $this->change($outreach, $this->source('BANGGA_SURABAYA'))->assertSessionHasErrors('confirm_destructive');

        $this->assertSame('BAZNAS', $outreach->refresh()->source->code);
        $this->assertSame(1, DB::table('mustahik_forms')->count());
        $this->assertSame(0, HistoryEvent::query()->count());
    }

    public function test_a_confirmed_change_to_bangga_surabaya_clears_mustahik_kifayah_and_their_signatures()
    {
        $outreach = $this->draft('CSR');
        $this->fillSourceForms($outreach);

        $this->change($outreach, $this->source('BANGGA_SURABAYA'), confirm: true)->assertSessionHasNoErrors();

        $outreach->refresh();
        $this->assertSame('BANGGA_SURABAYA', $outreach->source->code);
        $this->assertSame('Warga Demo', $outreach->nama, 'Outreach data itself is kept.');
        foreach (['mustahik_answers', 'mustahik_forms', 'kifayah_answers', 'kifayah_forms', 'signatures'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} is cleared.");
        }
        $this->assertNotNull(DB::table('files')->value('purged_at'));
        Storage::disk('local')->assertMissing('signatures/demo.png');
        $this->assertTrue(HistoryEvent::query()->sole()->metadata['cleared_forms']);
    }

    public function test_moving_to_baznas_adds_the_mustahik_and_kifayah_steps()
    {
        $outreach = $this->draft('BANGGA_SURABAYA');

        $this->change($outreach, $this->source('BAZNAS'))->assertSessionHasNoErrors();

        $this->get(route('outreaches.edit', $outreach))
            ->assertInertia(fn (Assert $page) => $page->has('steps', 10)->where('steps.5.key', 'mustahik'));
    }

    public function test_a_stale_version_is_rejected()
    {
        $outreach = $this->draft('BAZNAS');

        $this->change($outreach, $this->source('CSR'), version: 3)->assertSessionHasErrors('edit_version');
        $this->assertSame('BAZNAS', $outreach->refresh()->source->code);
    }

    public function test_inactive_choices_are_rejected_unless_already_selected()
    {
        $outreach = $this->draft('BAZNAS');
        $outreach->source->forceFill(['active' => false])->save();
        $inactiveType = OutreachType::factory()->create(['active' => false]);

        $this->change($outreach, $outreach->source, $inactiveType)->assertSessionHasErrors('type_id');

        $newType = OutreachType::factory()->create();
        $this->change($outreach, $outreach->source, $newType)->assertSessionHasNoErrors();
        $this->assertSame($newType->id, $outreach->refresh()->type_id);
    }

    public function test_only_the_owner_may_change_and_only_before_finalisasi()
    {
        $outreach = $this->draft('BAZNAS');
        $csr = $this->source('CSR');

        foreach ([User::factory()->create(), User::factory()->kepala()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->put(route('outreaches.source-type', $outreach), [
                'source_id' => $csr->id, 'type_id' => $outreach->type_id, 'edit_version' => 0,
            ])->assertForbidden();
        }

        $outreach->forceFill(['status' => OutreachStatus::PerluPerbaikan])->save();

        $this->change($outreach, $csr)->assertForbidden();
        $this->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'identitas']))
            ->assertInertia(fn (Assert $page) => $page->where('sourceTypeChoices', null));
    }

    public function test_the_identitas_step_flags_destructive_sources()
    {
        $outreach = $this->draft('BAZNAS');
        $this->source('BANGGA_SURABAYA');

        $this->actingAs($outreach->petugas)
            ->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'identitas']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('sourceTypeChoices.sources.0.name', 'BANGGA_SURABAYA')
                ->where('sourceTypeChoices.sources.0.destructive', true)
                ->where('sourceTypeChoices.sources.1.destructive', false));
    }
}
