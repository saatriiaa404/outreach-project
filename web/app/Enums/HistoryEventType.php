<?php

namespace App\Enums;

/**
 * Business history events (Database v1.1 section 11.1, D-031).
 */
enum HistoryEventType: string
{
    case Created = 'CREATED';
    case SectionSaved = 'SECTION_SAVED';
    case Finalized = 'FINALIZED';
    case Returned = 'RETURNED';
    case Refinalized = 'REFINALIZED';
    case Approved = 'APPROVED';
    case SpecialUnlocked = 'SPECIAL_UNLOCKED';
    case SpecialUnlockEdited = 'SPECIAL_UNLOCK_EDITED';
    case SpecialUnlockLocked = 'SPECIAL_UNLOCK_LOCKED';
    case SpecialUnlockSubmitted = 'SPECIAL_UNLOCK_SUBMITTED';
}
