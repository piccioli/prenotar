<?php

declare(strict_types=1);

use App\Models\Sezione;
use App\Models\Sottosezione;

it('label accessor returns nominativo (gia prefissato) con riferimento alla sezione, senza doppio prefisso', function (): void {
    $sezione = new Sezione(['nominativo' => 'SEZ. BERGAMO']);
    $sottosezione = new Sottosezione(['nominativo' => 'S.SEZ. ALBINO']);
    $sottosezione->setRelation('sezione', $sezione);

    expect($sottosezione->label)->toBe('S.SEZ. ALBINO (sez. rif. SEZ. BERGAMO)');
});
