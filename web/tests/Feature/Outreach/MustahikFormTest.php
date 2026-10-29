<?php

namespace Tests\Feature\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\MustahikAnswer;
use App\Models\MustahikForm;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\User;
use App\Support\MustahikFormSchema;
use App\Support\OutreachFormSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MustahikFormTest extends TestCase
{
    use RefreshDatabase;

    private function draft(string $sourceCode = 'BAZNAS'): Outreach
    {
        return Outreach::factory()
            ->for(OutreachSource::factory()->create(['code' => $sourceCode]), 'source')
            ->create([
                'outreach_date' => '2026-10-27',
                'nama' => 'Warga Demo',
                'nik' => 'DUMMY-NIK-001',
                'alamat_domisili' => 'Jl. Demo 1',
                'no_telp' => '080000000000',
            ])
            ->refresh();
    }

    /**
     * @return array<string, string>
     */
    private function completeForm(): array
    {
        return [
            'survey_date' => '2026-10-28',
            'kepala_keluarga_nama' => 'Kepala Demo',
            'kepala_keluarga_nik_no_kk' => '0000000000000001',
            'kepala_keluarga_alamat' => 'Jl. Demo 1',
            'kepala_keluarga_rt' => '001',
            'kepala_keluarga_rw' => '002',
            ...array_map(fn () => '1', MustahikFormSchema::QUESTIONS),
        ];
    }

    private function save(Outreach $outreach, array $data, int $version = 0, bool $next = false)
    {
        return $this->actingAs($outreach->petugas)->put(route('outreaches.mustahik.update', $outreach), [
            ...$data,
            'edit_version' => $version,
            'next' => $next ? '1' : '0',
        ]);
    }

    public function test_the_mustahik_step_opens_with_the_survey_date_prefilled_and_nothing_selected()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'mustahik']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('outreach/Mustahik')
                ->where('header.0.name', 'survey_date')
                ->where('header.0.value', '2026-10-27')
                ->where('header.1.value', null)
                ->has('questions', 25)
                ->where('questions.0.code', 'HOUSE_BUILDING_AREA')
                ->where('questions.0.value', null)
                ->where('questions.21.options.2', 'Lansia (tidak produktif)')
                ->where('wilayah', ['kelurahan' => 'Manukan Kulon', 'kecamatan' => 'Tandes'])
                ->where('noTelp', '080000000000')
                ->where('klien.nama', 'Warga Demo')
                ->where('steps.5.key', 'mustahik')
                ->where('steps.5.state', 'untouched'));
    }

    public function test_saving_stores_header_and_answers_as_option_codes()
    {
        $outreach = $this->draft();

        $this->save($outreach, ['survey_date' => '2026-10-28', 'kepala_keluarga_nama' => 'Kepala Demo', 'PHYSICAL_DEBT' => '3'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'mustahik']));

        $form = MustahikForm::query()->sole();
        $this->assertSame($outreach->petugas_id, $form->petugas_id);
        $this->assertSame('2026-10-28', $form->survey_date->toDateString());
        $this->assertSame('Kepala Demo', $form->kepala_keluarga_nama);
        $this->assertSame(['option' => '3'], MustahikAnswer::query()->sole()->answer);
        $this->assertSame(1, $outreach->refresh()->edit_version);
        $this->assertSame(['step' => 'mustahik'], HistoryEvent::query()->where('event_type', HistoryEventType::SectionSaved)->sole()->metadata);
    }

    public function test_clearing_an_answer_removes_it_and_unknown_options_are_rejected()
    {
        $outreach = $this->draft();
        $this->save($outreach, ['PHYSICAL_DEBT' => '3']);

        $this->save($outreach, ['PHYSICAL_DEBT' => ''], 1)->assertSessionHasNoErrors();
        $this->assertSame(0, MustahikAnswer::query()->count());

        $this->save($outreach, ['PHYSICAL_DEBT' => '5'], 2)->assertSessionHasErrors(['PHYSICAL_DEBT' => 'Hutang yang dipilih tidak valid.']);
    }

    public function test_finalisasi_needs_the_whole_header_and_all_25_answers()
    {
        $outreach = $this->draft();
        $this->assertCount(31, MustahikFormSchema::missing(null));

        $data = $this->completeForm();
        unset($data['kepala_keluarga_rw'], $data['PHYSICAL_SAVINGS']);
        $this->save($outreach, $data);

        $outreach->refresh();
        $this->assertSame(['RW', 'Tabungan'], OutreachFormSchema::missingForFinalization($outreach)['mustahik']);

        $this->save($outreach, $this->completeForm(), 1);
        $this->assertArrayNotHasKey('mustahik', OutreachFormSchema::missingForFinalization($outreach->refresh()));

        $this->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'narasi']))
            ->assertInertia(fn (Assert $page) => $page->where('steps.5.state', 'complete'));
    }

    public function test_nik_rt_and_rw_accept_digits_only_and_finalisasi_needs_16_nik_digits()
    {
        $outreach = $this->draft();

        $this->save($outreach, [...$this->completeForm(), 'kepala_keluarga_nik_no_kk' => '35780A0000000001'])
            ->assertSessionHasErrors(['kepala_keluarga_nik_no_kk' => 'NIK/No KK Kepala Keluarga hanya berisi angka, maksimal 16 digit.']);
        $this->save($outreach, [...$this->completeForm(), 'kepala_keluarga_nik_no_kk' => '00000000000000012'])
            ->assertSessionHasErrors('kepala_keluarga_nik_no_kk');
        $this->save($outreach, [...$this->completeForm(), 'kepala_keluarga_rt' => '1a'])->assertSessionHasErrors('kepala_keluarga_rt');
        $this->save($outreach, [...$this->completeForm(), 'kepala_keluarga_rw' => '0002'])->assertSessionHasErrors('kepala_keluarga_rw');

        // A NIK still being typed is kept in the Draft but blocks Finalisasi.
        $this->save($outreach, [...$this->completeForm(), 'kepala_keluarga_nik_no_kk' => '00000001', 'kepala_keluarga_rt' => '5'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['NIK/No KK Kepala Keluarga (harus 16 digit)'], OutreachFormSchema::missingForFinalization($outreach->refresh())['mustahik']);
        $this->assertSame('5', $outreach->mustahikForm->kepala_keluarga_rt);
    }

    public function test_autosave_stores_without_history_and_reports_conflicts()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->putJson(route('outreaches.mustahik.autosave', $outreach), ['edit_version' => 0, 'HOUSE_MCK' => '4'])
            ->assertOk()
            ->assertJsonPath('edit_version', 1);
        $this->assertSame(0, HistoryEvent::query()->count());

        $this->putJson(route('outreaches.mustahik.autosave', $outreach), ['edit_version' => 0, 'HOUSE_MCK' => '1'])
            ->assertConflict();
        $this->assertSame(['option' => '4'], MustahikAnswer::query()->sole()->answer);
    }

    public function test_bangga_surabaya_has_no_mustahik_step()
    {
        $outreach = $this->draft('BANGGA_SURABAYA');

        $this->save($outreach, ['HOUSE_MCK' => '1'])->assertForbidden();
        $this->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'mustahik']))
            ->assertInertia(fn (Assert $page) => $page->component('outreach/Edit')->where('step', 'klien'));
        $this->assertArrayNotHasKey('mustahik', OutreachFormSchema::missingForFinalization($outreach));
    }

    public function test_only_the_owner_may_save_and_only_while_editable()
    {
        $outreach = $this->draft();

        foreach ([User::factory()->create(), User::factory()->kepala()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->put(route('outreaches.mustahik.update', $outreach), ['edit_version' => 0])->assertForbidden();
        }

        $outreach->forceFill(['status' => OutreachStatus::MenungguPengesahan])->save();
        $this->save($outreach, ['HOUSE_MCK' => '1'])->assertForbidden();
        $this->assertSame(0, MustahikForm::query()->count());
    }

    public function test_save_and_continue_from_narasi_opens_mustahik()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)->put(route('outreaches.update', $outreach), [
            'step' => 'narasi', 'edit_version' => 0, 'next' => '1',
        ])->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'mustahik']));

        $this->save($outreach, ['HOUSE_MCK' => '2'], 1, next: true)
            ->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'kifayah']));
    }
}
