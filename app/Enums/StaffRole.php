<?php

namespace App\Enums;

enum StaffRole: string
{
    case OWNER = 'Owner';
    case ADMINISTRATEUR = 'Administrateur';
    case MANAGER = 'Manager';
    case TRADUCTEUR = 'Traducteur';
    case CHECKER = 'Checker';
    case CLEANER = 'Cleaner';
    case EDITEUR = 'Éditeur';
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
