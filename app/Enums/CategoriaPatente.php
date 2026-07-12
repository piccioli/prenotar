<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoriaPatente: string
{
    case B = 'B';
    case BE = 'BE';

    public function label(): string
    {
        return match ($this) {
            self::B => 'B',
            self::BE => 'B+E',
        };
    }
}
