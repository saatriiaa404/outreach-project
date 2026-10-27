<?php

namespace App\Enums;

enum OutreachStatus: string
{
    case Draft = 'DRAFT';
    case MenungguPengesahan = 'MENUNGGU_PENGESAHAN';
    case PerluPerbaikan = 'PERLU_PERBAIKAN';
    case Selesai = 'SELESAI';

    /**
     * States in which the owning Petugas may edit through the normal flow.
     */
    public function isEditableByPetugas(): bool
    {
        return in_array($this, [self::Draft, self::PerluPerbaikan], true);
    }

    /**
     * States on which Special Unlock may be applied (Workflow v2.1 section 25.1).
     */
    public function allowsSpecialUnlock(): bool
    {
        return in_array($this, [self::MenungguPengesahan, self::Selesai], true);
    }
}
