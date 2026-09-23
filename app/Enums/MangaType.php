<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MangaType: string implements HasLabel
{
    case MANGA = 'manga';
    case MANHWA = 'manhwa';
    case MANHUA = 'manhua';
    case COMIC = 'comic';
    case NOVEL = 'novel';
    
    public function getLabel(): ?string
    {
        return match ($this) {
            self::MANGA => 'Manga',
            self::MANHWA => 'Manhwa',
            self::MANHUA => 'Manhua',
            self::COMIC => 'Comic',
            self::NOVEL => 'Light Novel',
        };
    }
}
