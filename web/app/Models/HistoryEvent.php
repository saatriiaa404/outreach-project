<?php

namespace App\Models;

use App\Enums\HistoryEventType;
use App\Enums\UserRole;
use App\Support\SpecialUnlock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only business timeline of an Outreach (Database v1.1 section 11.1).
 *
 * @property int $id
 * @property int $outreach_id
 * @property int $actor_id
 * @property string $actor_name_snapshot
 * @property string $actor_role_snapshot
 * @property HistoryEventType $event_type
 * @property int|null $outreach_version_id
 * @property int|null $approval_id
 * @property string|null $description
 * @property string|null $reason
 * @property array<string, mixed>|null $field_changes
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
class HistoryEvent extends Model
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
            'event_type' => HistoryEventType::class,
            'field_changes' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Record an event performed by the given actor on an Outreach.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function record(Outreach $outreach, User $actor, HistoryEventType $type, array $attributes = []): self
    {
        $event = new self;
        $event->forceFill([
            'outreach_id' => $outreach->id,
            'actor_id' => $actor->id,
            'actor_name_snapshot' => $actor->name,
            'actor_role_snapshot' => $actor->role->value,
            'event_type' => $type,
            ...$attributes,
        ])->save();

        return $event;
    }

    /**
     * A data change: SECTION_SAVED in the normal flow, SPECIAL_UNLOCK_EDITED
     * with the session reason when the Admin edits during Special Unlock
     * (Workflow v2.1 25.8, D-056).
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function recordEdit(Outreach $outreach, User $actor, array $attributes = []): self
    {
        if ($actor->role !== UserRole::Admin) {
            return self::record($outreach, $actor, HistoryEventType::SectionSaved, $attributes);
        }

        return self::record($outreach, $actor, HistoryEventType::SpecialUnlockEdited, [
            'reason' => SpecialUnlock::session($outreach)?->reason,
            ...$attributes,
        ]);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('History events are append-only.'));
        static::deleting(fn () => throw new LogicException('History events are append-only.'));
    }

    /**
     * @return BelongsTo<Outreach, $this>
     */
    public function outreach(): BelongsTo
    {
        return $this->belongsTo(Outreach::class);
    }
}
