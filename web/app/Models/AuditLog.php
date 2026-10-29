<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Administrative audit trail, separate from the business history (Database
 * v1.1 11.2). Append-only; targets are a type and an id, not a polymorphic
 * foreign key.
 *
 * @property int $id
 * @property int $actor_id
 * @property string $action
 * @property string $target_type
 * @property int|null $target_id
 * @property array<string, mixed>|null $before_data
 * @property array<string, mixed>|null $after_data
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function record(User $actor, string $action, Model $target, array $attributes = []): self
    {
        $log = new self;
        $log->forceFill([
            'actor_id' => $actor->id,
            'action' => $action,
            'target_type' => $target->getTable(),
            'target_id' => $target->getKey(),
            ...$attributes,
        ])->save();

        return $log;
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit logs are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit logs are append-only.'));
    }
}
