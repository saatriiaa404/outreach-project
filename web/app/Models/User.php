<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password_hash
 * @property string|null $nip
 * @property string|null $pangkat
 * @property int|null $photo_file_id
 * @property string|null $phone
 * @property string|null $address
 * @property-read string|null $avatar
 * @property UserRole $role
 * @property UserStatus $status
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password_hash', 'nip', 'role', 'status'])]
#[Hidden(['password_hash'])]
#[Appends(['avatar'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The column holding the password hash (Database v1.1, D-030).
     *
     * @var string
     */
    protected $authPasswordName = 'password_hash';

    /**
     * Remember-me tokens are not used (D-030), so there is no token column.
     *
     * @var string
     */
    protected $rememberTokenName = '';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * The profile photo URL (D-067); the file id busts the browser cache.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(fn () => $this->photo_file_id
            ? route('profile.photo', ['user' => $this->id, 'v' => $this->photo_file_id])
            : null);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
