<?php

declare(strict_types=1);

use App\Models\Sezione;
use App\Models\Sottosezione;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('sezione has expected number of sottosezioni', function (): void {
    $sezione = Sezione::factory()->create();
    Sottosezione::factory(3)->create(['sezione_id' => $sezione->id]);

    expect($sezione->sottosezioni)->toHaveCount(3);
});

it('sottosezione belongs to sezione', function (): void {
    $sezione = Sezione::factory()->create(['nominativo' => 'SEZ. BERGAMO']);
    $sottosezione = Sottosezione::factory()->create(['sezione_id' => $sezione->id]);

    expect($sottosezione->sezione->nominativo)->toBe('SEZ. BERGAMO');
});

it('sottosezione label contains S.SEZ. prefix and sezione name', function (): void {
    $sezione = Sezione::factory()->create(['nominativo' => 'SEZ. BERGAMO']);
    $sottosezione = Sottosezione::factory()->create([
        'sezione_id' => $sezione->id,
        'nominativo' => 'ALBINO',
    ]);

    expect($sottosezione->label)
        ->toStartWith('S.SEZ.')
        ->toContain('SEZ. BERGAMO');
});
