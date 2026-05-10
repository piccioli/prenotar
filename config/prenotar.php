<?php

declare(strict_types=1);

return [
    'tagline' => env('APP_TAGLINE', 'Prenotar — Prenotazione torri di arrampicata GR Lombardia'),
    'fallback_email_domain' => env('FALLBACK_EMAIL_DOMAIN', 'grlomct.it'),

    'polizze' => [
        'trasporti' => '403463855',
        'all_risk' => '403448078',
    ],

    'documenti_giorni_minimi' => 10,
    'assicurazione_ore_minime' => 48,
];
