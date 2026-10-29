<?php

namespace Tests\Feature\Outreach;

use App\Enums\OutreachStatus;
use App\Enums\SignerType;
use App\Models\KifayahForm;
use App\Models\MustahikForm;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\Signature;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\OutreachFormSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function draft(string $sourceCode = 'BAZNAS'): Outreach
    {
        return Outreach::factory()
            ->for(OutreachSource::factory()->create(['code' => $sourceCode]), 'source')
            ->create(['nama' => 'Warga Demo', 'no_telp' => '080000000000'])
            ->refresh();
    }

    /**
     * A PNG data URL like the pad exports: transparent with optional ink.
     */
    private function pad(bool $ink = true): string
    {
        $image = imagecreatetruecolor(600, 220);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        if ($ink) {
            imagesetthickness($image, 3);
            imageline($image, 40, 150, 560, 60, imagecolorallocate($image, 17, 24, 39));
        }

        ob_start();
        imagepng($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    private function sign(Outreach $outreach, string $form, ?string $image = null, ?int $version = null)
    {
        return $this->actingAs($outreach->petugas)->post(route('outreaches.signatures.store', [$outreach, $form]), [
            'image' => $image ?? $this->pad(),
            'edit_version' => $version ?? $outreach->refresh()->edit_version,
        ]);
    }

    public function test_the_petugas_signs_mustahik_and_the_warga_signs_kifayah()
    {
        $outreach = $this->draft();

        $this->sign($outreach, 'mustahik')->assertSessionHasNoErrors()
            ->assertRedirect(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'tanda_tangan']));
        $this->sign($outreach, 'kifayah')->assertSessionHasNoErrors();

        $petugas = Signature::query()->findOrFail(MustahikForm::query()->sole()->signature_id);
        $this->assertSame(SignerType::Petugas, $petugas->signer_type);
        $this->assertSame($outreach->petugas_id, $petugas->signer_user_id);
        $this->assertSame($outreach->petugas->name, $petugas->signer_name_snapshot);

        $warga = Signature::query()->with('file')->findOrFail(KifayahForm::query()->sole()->signature_id);
        $this->assertSame(SignerType::Warga, $warga->signer_type);
        $this->assertNull($warga->signer_user_id);
        $this->assertSame('Warga Demo', $warga->signer_name_snapshot);
        $this->assertSame('image/png', $warga->file->mime_type);
        $this->assertStringStartsWith("signatures/{$outreach->id}/", $warga->file->storage_key);
        Storage::disk('local')->assertExists($warga->file->storage_key);

        $this->assertSame(2, $outreach->refresh()->edit_version);
        $this->assertArrayNotHasKey('tanda_tangan', OutreachFormSchema::missingForFinalization($outreach));
    }

    public function test_blank_or_non_png_images_are_rejected()
    {
        $outreach = $this->draft();

        $this->sign($outreach, 'mustahik', $this->pad(ink: false))->assertSessionHasErrors(['image' => 'Tanda tangan masih kosong.']);
        $this->sign($outreach, 'mustahik', 'data:image/svg+xml;base64,'.base64_encode('<svg/>'))->assertSessionHasErrors('image');
        $this->sign($outreach, 'mustahik', 'data:image/png;base64,'.base64_encode('bukan png'))->assertSessionHasErrors('image');

        $this->assertSame(0, Signature::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_warga_cannot_sign_before_the_klien_name_is_filled()
    {
        $outreach = $this->draft();
        $outreach->forceFill(['nama' => null])->save();

        $this->sign($outreach, 'kifayah')->assertSessionHasErrors('image');
    }

    public function test_signing_again_replaces_and_clearing_removes_the_signature()
    {
        $outreach = $this->draft();
        $this->sign($outreach, 'mustahik');
        $first = Signature::query()->with('file')->sole();

        $this->sign($outreach, 'mustahik');
        $this->assertModelMissing($first);
        $this->assertNotNull($first->file->refresh()->purged_at);
        Storage::disk('local')->assertMissing($first->file->storage_key);

        $this->delete(route('outreaches.signatures.destroy', [$outreach, 'mustahik']), ['edit_version' => $outreach->refresh()->edit_version])
            ->assertSessionHasNoErrors();
        $this->assertNull(MustahikForm::query()->sole()->signature_id);
        $this->assertSame(0, Signature::query()->count());
        $this->assertSame(['Tanda tangan Petugas', 'Tanda tangan Warga'], OutreachFormSchema::missingForFinalization($outreach)['tanda_tangan']);
    }

    public function test_changing_mustahik_answers_clears_only_the_petugas_signature()
    {
        $outreach = $this->draft();
        $this->sign($outreach, 'mustahik');
        $this->sign($outreach, 'kifayah');

        // Saving identical data keeps the signature.
        $this->put(route('outreaches.mustahik.update', $outreach), ['edit_version' => $outreach->refresh()->edit_version]);
        $this->assertNotNull(MustahikForm::query()->sole()->signature_id);

        $this->put(route('outreaches.mustahik.update', $outreach), ['edit_version' => $outreach->refresh()->edit_version, 'HOUSE_MCK' => '2']);
        $this->assertNull(MustahikForm::query()->sole()->signature_id);
        $this->assertNotNull(KifayahForm::query()->sole()->signature_id);
    }

    public function test_changing_kifayah_data_by_autosave_clears_the_warga_signature()
    {
        $outreach = $this->draft();
        $this->sign($outreach, 'kifayah');

        $this->putJson(route('outreaches.kifayah.autosave', $outreach), ['edit_version' => $outreach->refresh()->edit_version, 'expense_pregnant' => 0])
            ->assertOk();

        $this->assertNull(KifayahForm::query()->sole()->signature_id);
    }

    public function test_changing_identity_printed_on_the_forms_clears_their_signatures()
    {
        $outreach = $this->draft();
        $this->sign($outreach, 'mustahik');
        $this->sign($outreach, 'kifayah');

        // Pekerjaan is not printed on Mustahik or Kifayah.
        $this->put(route('outreaches.update', $outreach), ['step' => 'klien', 'edit_version' => $outreach->refresh()->edit_version, 'nama' => 'Warga Demo', 'no_telp' => '080000000000', 'pekerjaan' => 'Pedagang']);
        $this->assertNotNull(MustahikForm::query()->sole()->signature_id);
        $this->assertNotNull(KifayahForm::query()->sole()->signature_id);

        $this->put(route('outreaches.update', $outreach), ['step' => 'klien', 'edit_version' => $outreach->refresh()->edit_version, 'nama' => 'Warga Demo Baru', 'no_telp' => '080000000000', 'pekerjaan' => 'Pedagang']);
        $this->assertNotNull(MustahikForm::query()->sole()->signature_id, 'The name is not printed on the Mustahik form.');
        $this->assertNull(KifayahForm::query()->sole()->signature_id);

        $this->put(route('outreaches.update', $outreach), ['step' => 'klien', 'edit_version' => $outreach->refresh()->edit_version, 'nama' => 'Warga Demo Baru', 'no_telp' => '081111111111', 'pekerjaan' => 'Pedagang']);
        $this->assertNull(MustahikForm::query()->sole()->signature_id);
    }

    public function test_attachments_alone_do_not_clear_signatures()
    {
        $outreach = $this->draft();
        $this->sign($outreach, 'mustahik');

        $this->post(route('outreaches.attachments.store', $outreach), [
            'category' => 'KTP',
            'file' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(MustahikForm::query()->sole()->signature_id);
    }

    public function test_signature_images_are_private_and_access_is_checked()
    {
        $outreach = $this->draft();
        $this->sign($outreach, 'mustahik');
        $url = route('outreaches.signatures.show', [$outreach, 'mustahik']);

        $this->actingAs($outreach->petugas)->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
        $this->actingAs($outreach->petugas)->get(route('outreaches.signatures.show', [$outreach, 'kifayah']))->assertNotFound();
    }

    public function test_only_the_owner_signs_while_editable_and_never_for_bangga_surabaya()
    {
        $outreach = $this->draft();

        foreach ([User::factory()->create(), User::factory()->kepala()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->post(route('outreaches.signatures.store', [$outreach, 'mustahik']), ['image' => $this->pad(), 'edit_version' => 0])->assertForbidden();
        }

        $outreach->forceFill(['status' => OutreachStatus::MenungguPengesahan])->save();
        $this->sign($outreach, 'mustahik')->assertForbidden();

        $bangga = $this->draft('BANGGA_SURABAYA');
        $this->sign($bangga, 'mustahik')->assertNotFound();
        $this->assertArrayNotHasKey('tanda_tangan', OutreachFormSchema::missingForFinalization($bangga));
        $this->assertSame(0, StoredFile::query()->count());
    }

    public function test_the_tanda_tangan_step_lists_both_parties()
    {
        $outreach = $this->draft();
        $this->sign($outreach, 'kifayah');

        $this->get(route('outreaches.edit', ['outreach' => $outreach, 'step' => 'tanda_tangan']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('outreach/Signatures')
                ->where('parties.0.form', 'mustahik')
                ->where('parties.0.url', null)
                ->where('parties.1.signer', 'Warga Demo')
                ->where('parties.1.formMissing', 22)
                ->where('steps.8.key', 'tanda_tangan')
                ->where('steps.8.state', 'incomplete'));
    }
}
