<?php

declare(strict_types=1);

use App\Models\Torre;
use Database\Seeders\TorriSeeder;

it('creates exactly 2 torri', function (): void {
    $this->seed(TorriSeeder::class);
    expect(Torre::count())->toBe(2);
});

it('creates Torre 1 and Torre 2', function (): void {
    $this->seed(TorriSeeder::class);
    expect(Torre::where('nome', 'Torre di arrampicata 1')->exists())->toBeTrue();
    expect(Torre::where('nome', 'Torre di arrampicata 2')->exists())->toBeTrue();
});

it('is idempotent', function (): void {
    $this->seed(TorriSeeder::class);
    $this->seed(TorriSeeder::class);
    expect(Torre::count())->toBe(2);
});
