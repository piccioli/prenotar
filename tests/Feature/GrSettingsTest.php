<?php

declare(strict_types=1);

use App\Settings\GrSettings;

it('contains default emails_notifiche_gr after migration', function (): void {
    $settings = app(GrSettings::class);
    expect($settings->emails_notifiche_gr)->toContain('gr_cai_lombardia@cai.it');
});

it('contains default emails_assicurazione after migration', function (): void {
    $settings = app(GrSettings::class);
    expect($settings->emails_assicurazione)->toContain('info@lazzaroassicurazioni.it');
});

it('has default giorni_minimi = 10', function (): void {
    $settings = app(GrSettings::class);
    expect($settings->giorni_minimi_caricamento_documenti)->toBe(10);
});

it('persists changes', function (): void {
    $settings = app(GrSettings::class);
    $settings->giorni_minimi_caricamento_documenti = 15;
    $settings->save();

    $reloaded = app(GrSettings::class);
    expect($reloaded->giorni_minimi_caricamento_documenti)->toBe(15);
});
