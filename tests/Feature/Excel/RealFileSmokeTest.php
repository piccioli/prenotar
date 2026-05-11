<?php

declare(strict_types=1);

use App\Events\UserSetPasswordRequested;
use App\Models\Sezione;
use App\Models\Sottosezione;
use App\Models\User;
use App\Services\Import\ExcelImportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Event::fake([UserSetPasswordRequested::class]);
});

function realExcelPath(): string
{
    return base_path('DOCUMENTI PER LA PROGETTAZIONE/2026_MS_Sezioni_SottoSezioni_GR_Gruppi Regionali ETS.xlsx');
}

it('importa 152 sezioni e 77 sottosezioni LOMBARDIA dal file reale', function (): void {
    $realFile = realExcelPath();
    if (! file_exists($realFile)) {
        $this->markTestSkipped('File Excel reale non trovato.');
    }

    $service = app(ExcelImportService::class);
    $result = $service->import($realFile);

    expect(Sezione::where('regione', 'LOMBARDIA')->count())->toBe(152)
        ->and(Sottosezione::count())->toBe(77)
        ->and($result->righeImportate)->toBe(229)
        ->and($result->righeInErrore)->toBe(0);
})->group('integration');

it('crea 229 utenti con ruolo sezione dal file reale', function (): void {
    $realFile = realExcelPath();
    if (! file_exists($realFile)) {
        $this->markTestSkipped('File Excel reale non trovato.');
    }

    $service = app(ExcelImportService::class);
    $service->import($realFile);

    expect(User::role('sezione')->count())->toBe(229)
        ->and(User::where('email_is_fallback', true)->count())->toBeGreaterThanOrEqual(39);
})->group('integration');

it('il file reale ha 39+ sottosezioni senza email che ricevono email sintetica', function (): void {
    $realFile = realExcelPath();
    if (! file_exists($realFile)) {
        $this->markTestSkipped('File Excel reale non trovato.');
    }

    $service = app(ExcelImportService::class);
    $service->import($realFile);

    $fallbackUsers = User::where('email_is_fallback', true)->get();
    expect($fallbackUsers->count())->toBeGreaterThanOrEqual(39);

    // Verifica formato email fallback
    foreach ($fallbackUsers->take(5) as $user) {
        expect($user->email)->toMatch('/^\d+@grlomct\.it$/');
    }
})->group('integration');
