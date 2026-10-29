<?php

namespace Tests\Feature;

use App\Actions\Outreach\ApproveOutreach;
use App\Actions\Outreach\FinalizeOutreach;
use App\Models\AuditLog;
use App\Models\OutreachApproval;
use App\Models\SignatureTemplate;
use App\Models\StampTemplate;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsReadyOutreach;
use Tests\TestCase;

class TemplateManagementTest extends TestCase
{
    use BuildsReadyOutreach;
    use RefreshDatabase;

    private User $admin;

    private User $kepala;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
        $this->kepala = User::factory()->kepala()->create();
    }

    private function png(string $name = 'ttd.png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 300, 100);
    }

    private function uploadSignature(User $user, ?UploadedFile $file = null, ?User $kepala = null)
    {
        return $this->actingAs($user)->post(route('templates.signatures.store', $kepala ?? $this->kepala), ['file' => $file ?? $this->png()]);
    }

    public function test_only_admin_manages_templates()
    {
        foreach ([User::factory()->create(), $this->kepala] as $user) {
            $this->actingAs($user)->get(route('templates.index'))->assertForbidden();
            $this->uploadSignature($user)->assertForbidden();
            $this->actingAs($user)->post(route('templates.stamps.store'), ['file' => $this->png()])->assertForbidden();
        }

        $this->assertSame(0, SignatureTemplate::query()->count());
    }

    public function test_a_new_signature_version_becomes_active_and_retires_the_previous_one()
    {
        $this->uploadSignature($this->admin)->assertSessionHasNoErrors()->assertRedirect(route('templates.index'));
        $this->uploadSignature($this->admin, $this->png('ttd-baru.png'))->assertSessionHasNoErrors();

        [$first, $second] = SignatureTemplate::query()->orderBy('version_number')->get()->all();
        $this->assertSame([1, 2], [$first->version_number, $second->version_number]);
        $this->assertFalse($first->is_active);
        $this->assertNotNull($first->valid_until);
        $this->assertTrue($second->is_active);
        $this->assertSame($second->id, SignatureTemplate::activeFor($this->kepala)?->id);
        Storage::disk('local')->assertExists($second->file->storage_key);
        $this->assertSame(['SIGNATURE_TEMPLATE_ACTIVATED', 'SIGNATURE_TEMPLATE_ACTIVATED'], AuditLog::query()->pluck('action')->all());

        $this->actingAs($this->admin)->get(route('templates.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('templates/Index')
                ->has('kepala.0.versions', 2)
                ->where('kepala.0.versions.0.active', true));
        $this->actingAs($this->admin)->get(route('templates.signatures.image', $second))->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_uploads_must_be_real_png_or_jpg_images_within_the_limit()
    {
        $this->uploadSignature($this->admin, UploadedFile::fake()->create('ttd.pdf', 10, 'application/pdf'))->assertSessionHasErrors('file');
        $this->uploadSignature($this->admin, UploadedFile::fake()->createWithContent('ttd.png', 'bukan gambar'))->assertSessionHasErrors('file');
        $this->uploadSignature($this->admin, UploadedFile::fake()->image('besar.png')->size(3000))->assertSessionHasErrors('file');
        $this->uploadSignature($this->admin, null, User::factory()->create())->assertSessionHasErrors('file');

        $this->assertSame(0, SignatureTemplate::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_deactivating_without_replacement_blocks_approve()
    {
        $this->seed(MasterDataSeeder::class);
        $this->uploadSignature($this->admin);
        $template = SignatureTemplate::query()->sole();

        $this->actingAs($this->admin)->post(route('templates.signatures.deactivate', $template))->assertSessionHasNoErrors();
        $this->assertFalse($template->refresh()->is_active);

        $outreach = $this->ready();
        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);

        $this->expectException(ValidationException::class);
        app(ApproveOutreach::class)->handle($outreach->refresh(), $this->kepala, (int) $outreach->latestVersion()->value('id'));
    }

    public function test_an_approval_keeps_the_version_it_used()
    {
        $this->seed(MasterDataSeeder::class);
        $this->uploadSignature($this->admin);
        $used = SignatureTemplate::query()->sole();
        $outreach = $this->ready();
        app(FinalizeOutreach::class)->handle($outreach, $outreach->petugas, $outreach->edit_version);
        app(ApproveOutreach::class)->handle($outreach->refresh(), $this->kepala, (int) $outreach->latestVersion()->value('id'));

        $this->uploadSignature($this->admin, $this->png('ttd-baru.png'));

        $this->assertSame($used->id, OutreachApproval::query()->sole()->head_signature_template_id);
        $this->assertFalse($used->refresh()->is_active);
    }

    public function test_at_most_one_stamp_is_active()
    {
        $this->actingAs($this->admin)->post(route('templates.stamps.store'), ['file' => $this->png('stempel.png')])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('templates.stamps.store'), ['file' => UploadedFile::fake()->image('stempel.jpg')])->assertSessionHasNoErrors();

        $this->assertSame(1, StampTemplate::query()->where('is_active', true)->count());
        $this->assertSame(2, StampTemplate::query()->where('is_active', true)->value('version_number'));

        $stamp = StampTemplate::query()->where('is_active', true)->sole();
        $this->actingAs($this->admin)->post(route('templates.stamps.deactivate', $stamp))->assertSessionHasNoErrors();
        $this->assertSame(0, StampTemplate::query()->where('is_active', true)->count());
    }
}
