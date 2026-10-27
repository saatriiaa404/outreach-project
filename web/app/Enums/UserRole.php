<?php

namespace App\Enums;

enum UserRole: string
{
    case Petugas = 'PETUGAS';
    case Kepala = 'KEPALA';
    case Admin = 'ADMIN';
}
