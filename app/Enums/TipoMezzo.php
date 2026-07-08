<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoMezzo: string
{
    case Aziendale = 'aziendale';
    case Privato = 'privato';

    public function label(): string
    {
        return match ($this) {
            self::Aziendale => 'Aziendale (Montagna Servizi)',
            self::Privato => 'Privato',
        };
    }
}
