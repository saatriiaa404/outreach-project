<?php

namespace Tests\Feature\Outreach;

use App\Models\Outreach;
use App\Support\OutreachFormSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinalizationReadinessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every field that Finalisasi needs, with explicit zeros where nothing applies.
     *
     * @return array<string, mixed>
     */
    private function completeData(): array
    {
        $data = [];

        foreach (OutreachFormSchema::steps() as $step => $definition) {
            foreach ($definition['fields'] as $name => $field) {
                if ($step === 'identitas' || ! $field['required']) {
                    continue;
                }

                $data[$name] = match ($field['input']) {
                    'integer', 'money' => 0,
                    'date' => '1980-01-01',
                    'boolean' => false,
                    'select' => array_key_first($field['options']),
                    default => 'Isian demo',
                };
            }
        }

        return $data;
    }

    public function test_a_new_draft_lists_every_required_field_but_no_optional_ones()
    {
        $missing = OutreachFormSchema::missingForFinalization(Outreach::factory()->create());

        $this->assertSame(['klien', 'keluarga', 'rumah', 'narasi', 'mustahik', 'kifayah', 'lampiran'], array_keys($missing), 'The factory Source uses Mustahik and Kifayah.');
        $this->assertSame(['KTP', 'Kartu Keluarga (KK)', 'Foto Klien', 'Foto Rumah'], $missing['lampiran']);
        $this->assertCount(21, $missing['klien']);
        $this->assertCount(17, $missing['keluarga']);
        $this->assertNotContains('Penghasilan klien sebulan', $missing['klien'], 'Field 14 is optional (D-032).');
        $this->assertNotContains('Pendidikan terakhir', $missing['klien'], 'Field 17 is optional (D-032).');
        $this->assertNotContains('Perhiasan', $missing['rumah']);
        $this->assertNotContains('Nama kendaraan lainnya', $missing['rumah']);
    }

    public function test_explicit_zeros_and_no_answers_complete_the_form()
    {
        $outreach = Outreach::factory()->create($this->completeData());

        $this->assertSame(['mustahik', 'kifayah', 'lampiran'], array_keys(OutreachFormSchema::missingForFinalization($outreach)));
        $this->assertSame('complete', OutreachFormSchema::completion($outreach, 'rumah'));
    }

    public function test_conditional_fields_are_required_only_when_their_answer_applies()
    {
        $outreach = Outreach::factory()->create([
            ...$this->completeData(),
            'bantuan_pernah_diterima' => true,
            'punya_hutang' => true,
            'jumlah_kendaraan_lainnya' => 1,
        ]);

        $missing = OutreachFormSchema::missingForFinalization($outreach);

        $this->assertSame(['Uraian bantuan yang pernah diterima', 'Berapa besar pinjaman / hutang'], $missing['klien']);
        $this->assertSame(['Nama kendaraan lainnya'], $missing['rumah']);
    }

    public function test_totals_are_derived_and_stay_open_while_a_component_is_empty()
    {
        $outreach = Outreach::factory()->create([
            ...$this->completeData(),
            'penghasilan_kepala_keluarga' => 1_500_000,
            'penghasilan_lainnya' => 250_000,
            'pengeluaran_listrik' => 100_000,
        ]);

        $this->assertSame(1_750_000, OutreachFormSchema::total($outreach, 'pendapatan'));
        $this->assertSame(100_000, OutreachFormSchema::total($outreach, 'pengeluaran'));

        $outreach->forceFill(['penghasilan_suami_istri' => null])->save();

        $this->assertNull(OutreachFormSchema::total($outreach->refresh(), 'pendapatan'), 'An empty component is not read as 0.');
    }

    public function test_the_stepper_reports_how_many_fields_are_missing()
    {
        $outreach = Outreach::factory()->create([...$this->completeData(), 'agama' => null, 'pekerjaan' => null]);

        $this->actingAs($outreach->petugas)
            ->get(route('outreaches.edit', $outreach))
            ->assertInertia(fn (Assert $page) => $page
                ->where('steps.1.state', 'incomplete')
                ->where('steps.1.missing', 2)
                ->where('steps.2.missing', 0));
    }
}
