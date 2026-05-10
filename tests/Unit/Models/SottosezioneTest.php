<?php

declare(strict_types=1);

use App\Models\Sezione;
use App\Models\Sottosezione;

it('label accessor produces S.SEZ. prefix with sezione reference', function (): void {
    $sezione = new Sezione(['nominativo' => 'SEZ. BERGAMO']);
    $sottosezione = new Sottosezione(['nominativo' => 'S.SEZ. ALBINO']);
    $sottosezione->setRelation('sezione', $sezione);

    expect($sottosezione->label)->toStartWith('S.SEZ.')->toContain('SEZ. BERGAMO');
});
