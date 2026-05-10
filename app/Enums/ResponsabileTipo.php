<?php

declare(strict_types=1);

namespace App\Enums;

enum ResponsabileTipo: string
{
    case Istruttore = 'istruttore';
    case Accompagnatore = 'accompagnatore';
    case SoccorsoAlpino = 'soccorso_alpino';
    case Altro = 'altro';

    public function label(): string
    {
        return match ($this) {
            self::Istruttore => 'Istruttore CAI',
            self::Accompagnatore => 'Accompagnatore CAI',
            self::SoccorsoAlpino => 'Soccorso Alpino',
            self::Altro => 'Altro',
        };
    }
}
