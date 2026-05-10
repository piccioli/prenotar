<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Enums\ResponsabileTipo;

it('PrenotazioneStatus::Concluso is final', function (): void {
    expect(PrenotazioneStatus::Concluso->isFinal())->toBeTrue();
});

it('PrenotazioneStatus::Annullata is final', function (): void {
    expect(PrenotazioneStatus::Annullata->isFinal())->toBeTrue();
});

it('PrenotazioneStatus::Bozza is not final', function (): void {
    expect(PrenotazioneStatus::Bozza->isFinal())->toBeFalse();
});

it('PrenotazioneStatus::Inviata is active (blocks new submissions)', function (): void {
    expect(PrenotazioneStatus::Inviata->isActive())->toBeTrue();
});

it('PrenotazioneStatus::Bozza is not active', function (): void {
    expect(PrenotazioneStatus::Bozza->isActive())->toBeFalse();
});

it('ResponsabileTipo::SoccorsoAlpino has a label', function (): void {
    expect(ResponsabileTipo::SoccorsoAlpino->label())->not->toBeNull()->toBeString();
});

it('all PrenotazioneStatus values have a label', function (): void {
    foreach (PrenotazioneStatus::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
});
