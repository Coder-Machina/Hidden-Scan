<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum MangaStatus: string implements HasLabel, HasColor
{
    case EN_COURS = 'en_cours';
    case TERMINE = 'termine';
    case PAUSE = 'pause';
    case ABANDONNE = 'abandonne';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EN_COURS => 'En cours',
            self::TERMINE => 'Terminé',
            self::PAUSE => 'En pause',
            self::ABANDONNE => 'Abandonné',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EN_COURS => 'success',
            self::TERMINE => 'info',
            self::PAUSE => 'warning',
            self::ABANDONNE => 'danger',
        };
    }
}
