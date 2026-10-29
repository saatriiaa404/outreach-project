<?php

namespace App\Enums;

/**
 * Administrative lifecycle of a Special Unlock request (Workflow v2.1 31.4),
 * separate from the Outreach business state.
 */
enum SpecialUnlockRequestStatus: string
{
    case Pending = 'PENDING';
    case Processing = 'PROCESSING';
    case Rejected = 'REJECTED';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Processing => 'Diproses',
            self::Rejected => 'Ditolak',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Open requests block a new request for the same Outreach (D-055).
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Pending, self::Processing];
    }
}
