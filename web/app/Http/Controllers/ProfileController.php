<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\ManageProfile;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The optional account profile (D-067): the owner edits it from Settings,
 * the ADMIN from the Users page; both post to update().
 */
class ProfileController extends Controller
{
    /**
     * The photo upload limit, in kilobytes.
     */
    public const MAX_PHOTO_KILOBYTES = 10 * 1024;

    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Profile', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar' => $user->avatar,
            ],
            'maxPhotoKilobytes' => self::MAX_PHOTO_KILOBYTES,
        ]);
    }

    public function update(Request $request, User $user, ManageProfile $profiles): RedirectResponse
    {
        Gate::authorize('updateProfile', $user);
        $request->validate([
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9 ]{6,20}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'file', 'max:'.self::MAX_PHOTO_KILOBYTES],
            'remove_photo' => ['boolean'],
        ], ['phone.regex' => 'Nomor telepon hanya berisi angka (boleh diawali +), 6 sampai 20 karakter.'], [
            'phone' => 'nomor telepon', 'address' => 'alamat domisili', 'photo' => 'foto profil',
        ]);

        $profiles->update($request->user(), $user, [
            'phone' => $request->filled('phone') ? trim($request->string('phone')->toString()) : null,
            'address' => $request->filled('address') ? trim($request->string('address')->toString()) : null,
        ], $request->file('photo'), $request->boolean('remove_photo'));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Profil {$user->name} tersimpan."]);

        return back();
    }

    public function photo(User $user): StreamedResponse
    {
        Gate::authorize('viewProfile', $user);
        $file = $user->photo_file_id ? StoredFile::query()->find($user->photo_file_id) : null;
        abort_if($file === null || $file->purged_at !== null || ! Storage::disk($file->storage_disk)->exists($file->storage_key), 404);

        return Storage::disk($file->storage_disk)->response($file->storage_key, 'foto-profil.jpg', [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
