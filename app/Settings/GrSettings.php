<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GrSettings extends Settings
{
    /** @var string[] */
    public array $emails_notifiche_gr;

    /** @var string[] */
    public array $emails_assicurazione;

    public ?string $firma_presidente_path = null;

    public ?string $documento_presidente_path = null;

    public ?string $presidente_nome = null;

    public ?string $presidente_nato_a = null;

    /** Data di nascita in formato ISO (YYYY-MM-DD). */
    public ?string $presidente_data_nascita = null;

    public int $giorni_minimi_caricamento_documenti = 10;

    public int $ore_minime_richiesta_assicurazione = 48;

    public static function group(): string
    {
        return 'gr';
    }
}
