<?php

namespace App\Enums;

enum StaffRole: string
{
    case ADMIN = 'Admin';
    case MODO = 'Modo';
    case UPLOADER = 'Uploader';
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
