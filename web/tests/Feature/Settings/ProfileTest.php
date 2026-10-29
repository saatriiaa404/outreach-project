<?php

namespace Tests\Feature\Settings;

use App\Models\AuditLog;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The optional account profile (D-067).
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function save(User $actor, User $account, array $data = [])
    {
        return $this->actingAs($actor)->post(route('profile.update', $account), [
            'phone' => '081234567890', 'address' => 'Jl. Contoh No. 1, Surabaya', ...$data,
        ]);
    }

    public function test_settings_open_on_the_profile_page()
    {
        $user = User::factory()->create(['name' => 'Petugas Uji']);

        $this->actingAs($user)->get('/settings')->assertRedirect('/settings/profile');
        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('settings/Profile')->where('profile.name', 'Petugas Uji'));
    }

    public function test_a_user_fills_their_own_profile_with_a_compressed_photo()
    {
        $user = User::factory()->create();

        $this->save($user, $user, ['photo' => UploadedFile::fake()->image('foto.png', 3000, 3000)])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('081234567890', $user->phone);
        $this->assertSame('Jl. Contoh No. 1, Surabaya', $user->address);
        $file = StoredFile::query()->findOrFail($user->photo_file_id);
        $this->assertSame('image/jpeg', $file->mime_type);
        $this->assertSame(2000, $file->width);
        Storage::disk('local')->assertExists($file->storage_key);
        $this->assertNotNull($user->avatar);
        $this->actingAs($user)->get($user->avatar)->assertOk();
        $this->assertTrue(AuditLog::query()->where('action', 'USER_PROFILE_UPDATED')->where('target_id', $user->id)->exists());
    }

    public function test_replacing_or_removing_the_photo_purges_the_old_file()
    {
        $user = User::factory()->create();
        $this->save($user, $user, ['photo' => UploadedFile::fake()->image('lama.jpg')]);
        $old = StoredFile::query()->findOrFail($user->refresh()->photo_file_id);

        $this->save($user, $user, ['photo' => UploadedFile::fake()->image('baru.jpg')]);
        Storage::disk('local')->assertMissing($old->storage_key);
        $this->assertNotNull($old->refresh()->purged_at);

        $this->save($user, $user, ['remove_photo' => true])->assertSessionHasNoErrors();
        $this->assertNull($user->refresh()->photo_file_id);
        $this->assertNull($user->avatar);
    }

    public function test_the_admin_edits_any_profile_but_others_only_their_own()
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $other = User::factory()->create();

        $this->save($admin, $petugas)->assertSessionHasNoErrors();
        $this->assertSame('081234567890', $petugas->refresh()->phone);

        $this->save($other, $petugas, ['phone' => '089999999999'])->assertForbidden();
        $this->assertSame('081234567890', $petugas->refresh()->phone);

        $this->save($petugas, $petugas, ['photo' => UploadedFile::fake()->image('foto.jpg')]);
        $this->actingAs($other)->get($petugas->refresh()->avatar)->assertForbidden();
        $this->actingAs($admin)->get($petugas->avatar)->assertOk();
    }

    public function test_the_profile_is_validated_and_name_stays_with_the_admin()
    {
        $user = User::factory()->create(['name' => 'Nama Tetap']);

        $this->save($user, $user, ['phone' => 'telepon saya'])->assertSessionHasErrors('phone');
        $this->save($user, $user, ['address' => str_repeat('a', 501)])->assertSessionHasErrors('address');
        $this->save($user, $user, ['photo' => UploadedFile::fake()->createWithContent('foto.png', 'bukan gambar')])->assertSessionHasErrors('photo');
        $this->save($user, $user, ['name' => 'Nama Lain'])->assertSessionHasNoErrors();

        $this->assertSame('Nama Tetap', $user->refresh()->name);
    }
}
