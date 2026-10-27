<?php

namespace Tests\Feature\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\KifayahAnswer;
use App\Models\KifayahForm;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\User;
use App\Support\KifayahFormSchema;
use App\Support\OutreachFormSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KifayahFormTest extends TestCase
{
    use RefreshDatabase;

    private function draft(string $sourceCode = 'CSR'): Outreach
    {
        return Outreach::factory()
            ->for(OutreachSource::factory()->create(['code' => $sourceCode]), 'source')
            ->create([
                'nama' => 'Warga Demo',
                'nik' => 'DUMMY-NIK-001',
                'alamat_domisili' => 'Jl. Demo 1',
                'no_telp' => '080000000000',
                'penghasilan_kepala_keluarga' => 1_500_000,
                'punya_hutang' => true,
            ])
            ->refresh();
    }

    /**
     * @return array<string, string>
     */
    private function completeForm(): array
    {
        return [
            ...array_map(fn () => '0', KifayahFormSchema::FIELDS),
            ...array_map(fn () => '1', KifayahFormSchema::QUESTIONS),
        ];
    }

    private function save(Outreach $outreach, array $data, int $version = 0)
    {
        return $this->actingAs($outreach->petugas)->put(route('outreaches.kifayah.update', $outreach), [
            ...$data,
            'edit_version' => $version,
        ]);
    }

    public function test_the_kifayah_step_shows_outreach_identity_and_nothing_filled()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'kifayah']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('outreach/Kifayah')
                ->where('identitas', [
                    'nama' => 'Warga Demo',
                    'nik' => 'DUMMY-NIK-001',
                    'alamat' => 'Jl. Demo 1',
                    'kelurahan' => 'Manukan Kulon',
                    'kecamatan' => 'Tandes',
                    'no_hp' => '080000000000',
                ])
                ->has('fields', 15)
                ->where('fields.0.value', null)
                ->has('questions', 7)
                ->where('questions.6.code', 'DEBT')
                ->where('questions.6.value', null)
                ->where('questions.1.options.3', 'Lebih dari 900 Watt')
                ->where('steps.6.key', 'kifayah')
                ->where('steps.6.state', 'untouched'));
    }

    public function test_zero_and_empty_stay_distinct()
    {
        $outreach = $this->draft();

        $this->save($outreach, ['expense_pregnant' => '0', 'expense_housewife' => '', 'income_head_family' => '2500000', 'DEBT' => '2'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'kifayah']));

        $form = KifayahForm::query()->sole();
        $this->assertSame(0, $form->getAttribute('expense_pregnant'));
        $this->assertNull($form->getAttribute('expense_housewife'), 'An empty field is not turned into 0.');
        $this->assertSame(2_500_000, $form->getAttribute('income_head_family'));
        $this->assertSame(['option' => '2'], KifayahAnswer::query()->sole()->answer);
        $this->assertContains('Ibu Rumah Tangga', KifayahFormSchema::missing($form));
        $this->assertNotContains('Ibu Hamil', KifayahFormSchema::missing($form));
        $this->assertSame(['step' => 'kifayah'], HistoryEvent::query()->where('event_type', HistoryEventType::SectionSaved)->sole()->metadata);
    }

    public function test_negative_non_numeric_and_unknown_values_are_rejected()
    {
        $outreach = $this->draft();

        $this->save($outreach, [
            'expense_disability' => '-1',
            'income_other_members' => 'banyak',
            'higher_education_count' => '1.5',
            'ELECTRICITY' => '4',
        ])->assertSessionHasErrors([
            'expense_disability' => 'Penyandang Disabilitas minimal 0.',
            'income_other_members' => 'Pendapatan Anggota Lain per Bulan harus berupa bilangan bulat.',
            'higher_education_count',
            'ELECTRICITY' => 'Penerangan Listrik yang dipilih tidak valid.',
        ]);

        $this->assertSame(0, KifayahForm::query()->count());
    }

    public function test_finalisasi_needs_every_field_explicitly_and_all_seven_answers()
    {
        $outreach = $this->draft();
        $this->assertCount(22, KifayahFormSchema::missing(null));

        $data = $this->completeForm();
        unset($data['expense_widow_not_working'], $data['SAVINGS']);
        $this->save($outreach, $data);
        $this->assertSame(['Status Janda (Tidak Bekerja)', 'Memiliki Tabungan'], OutreachFormSchema::missingForFinalization($outreach->refresh())['kifayah']);

        $this->save($outreach, $this->completeForm(), 1);
        $this->assertArrayNotHasKey('kifayah', OutreachFormSchema::missingForFinalization($outreach->refresh()));
    }

    public function test_answers_are_never_taken_from_outreach_data()
    {
        // The Klien has a debt in Outreach, but Kifayah DEBT stays unanswered.
        $outreach = $this->draft();

        $this->save($outreach, ['expense_pregnant' => '0']);

        $this->assertSame(0, KifayahAnswer::query()->count());
        $this->assertNull(KifayahForm::query()->sole()->getAttribute('income_head_family'));
    }

    public function test_autosave_stores_without_history_and_reports_conflicts()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->putJson(route('outreaches.kifayah.autosave', $outreach), ['edit_version' => 0, 'expense_pregnant' => 0])
            ->assertOk()
            ->assertJsonPath('edit_version', 1);
        $this->assertSame(0, HistoryEvent::query()->count());

        $this->putJson(route('outreaches.kifayah.autosave', $outreach), ['edit_version' => 0, 'expense_pregnant' => 5])
            ->assertConflict();
        $this->assertSame(0, KifayahForm::query()->sole()->getAttribute('expense_pregnant'));
    }

    public function test_bangga_surabaya_has_no_kifayah_and_only_the_owner_may_save()
    {
        $bangga = $this->draft('BANGGA_SURABAYA');
        $this->save($bangga, ['expense_pregnant' => '0'])->assertForbidden();
        $this->assertArrayNotHasKey('kifayah', OutreachFormSchema::missingForFinalization($bangga));

        $outreach = $this->draft('BAZNAS');
        foreach ([User::factory()->create(), User::factory()->kepala()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->put(route('outreaches.kifayah.update', $outreach), ['edit_version' => 0])->assertForbidden();
        }

        $outreach->forceFill(['status' => OutreachStatus::MenungguPengesahan])->save();
        $this->save($outreach, ['expense_pregnant' => '0'])->assertForbidden();
        $this->assertSame(0, KifayahForm::query()->count());
    }

    public function test_save_and_continue_from_mustahik_opens_kifayah_then_lampiran()
    {
        $outreach = $this->draft();

        $this->actingAs($outreach->petugas)
            ->put(route('outreaches.mustahik.update', $outreach), ['edit_version' => 0, 'next' => '1'])
            ->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'kifayah']));

        $this->put(route('outreaches.kifayah.update', $outreach), ['edit_version' => 1, 'next' => '1'])
            ->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'lampiran']));
    }
}
