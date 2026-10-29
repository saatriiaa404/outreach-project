<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\ManageUsers;
use App\Concerns\PasswordValidationRules;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OutreachApproval;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Account management for the ADMIN (design.md, D-014, D-041, D-060).
 */
class UserController extends Controller
{
    use PasswordValidationRules;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $nipUsed = OutreachApproval::query()->where('decision', OutreachApproval::APPROVED)->distinct()->pluck('kepala_id')->flip();

        return Inertia::render('users/Index', [
            'users' => User::query()->orderBy('role')->orderBy('name')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'nip' => $user->nip,
                'pangkat' => $user->pangkat,
                'nip_used' => $nipUsed->has($user->id),
                'is_self' => $user->id === $request->user()->id,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request, ManageUsers $users): RedirectResponse
    {
        Gate::authorize('create', User::class);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => $this->passwordRules(),
            'nip' => $this->nipRules(),
            'pangkat' => ['nullable', 'string', 'max:100'],
        ], [], $this->attributes());

        $user = $users->create($request->user(), [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'role' => UserRole::from($request->string('role')->toString()),
            'password' => $request->string('password')->toString(),
            'nip' => $request->filled('nip') ? $request->string('nip')->toString() : null,
            'pangkat' => $request->filled('pangkat') ? $request->string('pangkat')->toString() : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Akun {$user->name} dibuat."]);

        return to_route('users.index');
    }

    public function update(Request $request, User $user, ManageUsers $users): RedirectResponse
    {
        Gate::authorize('update', $user);
        // The role is fixed at creation (D-060), so it is not accepted here.
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'nip' => $this->nipRules(),
            'pangkat' => ['nullable', 'string', 'max:100'],
            'nip_correction_reason' => ['nullable', 'string', 'max:15000'],
        ], [], $this->attributes());

        $users->update($request->user(), $user, [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'status' => UserStatus::from($request->string('status')->toString()),
            'nip' => $request->filled('nip') ? $request->string('nip')->toString() : null,
            'pangkat' => $request->filled('pangkat') ? $request->string('pangkat')->toString() : null,
            'nip_correction_reason' => $request->filled('nip_correction_reason') ? $request->string('nip_correction_reason')->toString() : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Akun {$request->string('name')} tersimpan."]);

        return to_route('users.index');
    }

    public function resetPassword(Request $request, User $user, ManageUsers $users): RedirectResponse
    {
        Gate::authorize('update', $user);
        $request->validate(['password' => $this->passwordRules()], [], $this->attributes());

        $users->resetPassword($request->user(), $user, $request->string('password')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Password {$user->name} direset. Minta pengguna menggantinya sendiri."]);

        return to_route('users.index');
    }

    /**
     * Digits only, up to the 18 characters of the column; the exact format is
     * still PENDING VALIDATION (D-041).
     *
     * @return list<mixed>
     */
    private function nipRules(): array
    {
        return ['nullable', 'string', 'regex:/^\d{1,18}$/'];
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return ['name' => 'nama', 'role' => 'peran', 'nip' => 'NIP', 'pangkat' => 'pangkat', 'nip_correction_reason' => 'alasan koreksi'];
    }
}
