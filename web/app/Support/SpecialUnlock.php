<?php

namespace App\Support;

use App\Enums\HistoryEventType;
use App\Models\HistoryEvent;
use App\Models\Outreach;

/**
 * Whether Special Unlock is open on an Outreach. It is not a status
 * (Database v1.1 3.x, 23.2): a session is open when the latest Special Unlock
 * event is SPECIAL_UNLOCKED, and closes with SPECIAL_UNLOCK_LOCKED or
 * SPECIAL_UNLOCK_SUBMITTED (D-056). Callers that change state check it under
 * the Outreach row lock.
 */
class SpecialUnlock
{
    private const SESSION_EVENTS = [
        HistoryEventType::SpecialUnlocked,
        HistoryEventType::SpecialUnlockLocked,
        HistoryEventType::SpecialUnlockSubmitted,
    ];

    /**
     * The SPECIAL_UNLOCKED event of the open session, which carries its reason.
     */
    public static function session(Outreach $outreach): ?HistoryEvent
    {
        $latest = HistoryEvent::query()
            ->where('outreach_id', $outreach->id)
            ->whereIn('event_type', self::SESSION_EVENTS)
            ->latest('id')
            ->first();

        return $latest?->event_type === HistoryEventType::SpecialUnlocked ? $latest : null;
    }

    public static function isActive(Outreach $outreach): bool
    {
        return self::session($outreach) !== null;
    }
}
