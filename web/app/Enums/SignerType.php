<?php

namespace App\Enums;

/**
 * Who signs directly on the device (Database v1.1 section 10.1).
 */
enum SignerType: string
{
    case Petugas = 'PETUGAS';
    case Warga = 'WARGA';
}
