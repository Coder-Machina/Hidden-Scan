<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum ChapterStatus: string implements HasLabel, HasColor
{
    case BROUILLON = 'brouillon';
    case CONTROLE = 'controle';
    case PROGRAMME = 'programme';
    case PUBLIE = 'publie';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::CONTROLE => 'En contrôle',
            self::PROGRAMME => 'Programmé',
            self::PUBLIE => 'Publié',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BROUILLON => 'gray',
            self::CONTROLE => 'warning',
            self::PROGRAMME => 'info',
            self::PUBLIE => 'success',
        };
    }
}
