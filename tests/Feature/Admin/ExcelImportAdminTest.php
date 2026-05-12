<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\ExcelImportResource;
use App\Models\ExcelImport;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

test('ExcelImport resource è sola lettura: canCreate ritorna false', function (): void {
    expect(ExcelImportResource::canCreate())->toBeFalse();
});

test('ExcelImport record può essere creato via modello', function (): void {
    ExcelImport::create([
        'filename' => 'test.xlsx',
        'hash' => md5('test'),
        'imported_by' => $this->admin->id,
        'righe_importate' => 10,
        'righe_aggiornate' => 5,
        'righe_in_errore' => 0,
    ]);

    expect(ExcelImport::count())->toBe(1);
});
