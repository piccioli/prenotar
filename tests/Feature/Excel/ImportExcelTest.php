<?php

declare(strict_types=1);

use App\Events\UserSetPasswordRequested;
use App\Models\ExcelImport;
use App\Models\Sezione;
use App\Models\Sottosezione;
use App\Models\User;
use App\Services\Import\ExcelImportService;
use App\Services\Import\Exceptions\ImportAlreadyDoneException;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Event::fake([UserSetPasswordRequested::class]);
});

function miniXlsxPath(): string
{
    return base_path('tests/fixtures/excel/mini.xlsx');
}

it('happy path: importa 3 sezioni LOMBARDIA + 2 sottosezioni valide', function (): void {
    $service = app(ExcelImportService::class);
    $result = $service->import(miniXlsxPath());

    // Righe importate: 3 sezioni + 2 sottosezioni valide = 5 (l'orfana va in errore)
    expect($result->righeImportate)->toBe(5)
        ->and($result->righeAggiornate)->toBe(0)
        ->and($result->righeInErrore)->toBe(1) // l'orfana con sezione padre non trovata
        ->and($result->userCreati)->toBe(5);

    expect(Sezione::count())->toBe(3)
        ->and(Sottosezione::count())->toBe(2)
        ->and(User::role('sezione')->count())->toBe(5)
        ->and(ExcelImport::count())->toBe(1);
});

it('filtra le righe con regione diversa da LOMBARDIA', function (): void {
    $service = app(ExcelImportService::class);
    $service->import(miniXlsxPath());

    // PIEMONTE e EXTRA REGIONE non devono creare record
    expect(Sezione::where('regione', 'PIEMONTE')->count())->toBe(0)
        ->and(Sottosezione::where('regione', 'EXTRA REGIONE')->count())->toBe(0);
});

it('assegna email fallback alle righe senza email valida', function (): void {
    $service = app(ExcelImportService::class);
    $service->import(miniXlsxPath());

    // SEZ. TEST GAMMA (9299903) non aveva email -> fallback
    $user = User::where('codice_cai', '9299903')->first();
    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('9299903@grlomct.it')
        ->and($user->email_is_fallback)->toBeTrue();

    // SEZ. TEST ALFA (9299901) aveva email reale
    $userAlfa = User::where('codice_cai', '9299901')->first();
    expect($userAlfa->email)->toBe('alfa@cai.it')
        ->and($userAlfa->email_is_fallback)->toBeFalse();
});

it('è idempotente: rilancia ImportAlreadyDoneException sulla stessa hash', function (): void {
    $service = app(ExcelImportService::class);
    $service->import(miniXlsxPath());

    expect(fn () => $service->import(miniXlsxPath()))
        ->toThrow(ImportAlreadyDoneException::class);
});

it('con --force aggiorna anagrafiche senza ricreare utenti né rimandare notifica', function (): void {
    $service = app(ExcelImportService::class);
    $service->import(miniXlsxPath());

    // Reset event fake per contare solo il secondo import
    Event::fake([UserSetPasswordRequested::class]);

    $result = $service->import(miniXlsxPath(), null, force: true);

    expect($result->righeImportate)->toBe(0)
        ->and($result->righeAggiornate)->toBe(5)
        ->and($result->userCreati)->toBe(0);

    Event::assertNotDispatched(UserSetPasswordRequested::class);
    expect(User::role('sezione')->count())->toBe(5);
});

it('soft-deactivate: codici rimossi dal file diventano is_active=false', function (): void {
    $service = app(ExcelImportService::class);
    $service->import(miniXlsxPath());

    // Simulo un secondo file in cui 9299901 è sparita: creo un file mini senza quella sezione
    // Creo un file temporaneo con solo 2 sezioni
    $spreadsheet = new Spreadsheet;
    $s0 = $spreadsheet->getActiveSheet();
    $s0->fromArray([
        ['codice', 'nominativo', 'regione', 'provincia', 'anno di fondazione', 'iscritti', 'sitoweb', 'email', 'telefono', 'PIVA', 'CF', 'PEC', 'indirizzo Sede Legale', 'Presidente', 'Componenti', 'Ente del Terzo Settore', 'Organo Di Controllo', 'Sezione ref. tappa SICAI', 'Sez. referente regionale'],
        ['9299902', 'SEZ. TEST BETA', 'LOMBARDIA', 'BG', 1960, 200, 'www.beta.it', 'beta@cai.it', '', null, null, null, 'Via Beta 2', 'Giovanni Bianchi', null, null, null, null, null],
        ['9299903', 'SEZ. TEST GAMMA', 'LOMBARDIA', 'CO', 1970, 300, '', '', '', null, null, null, 'Via Gamma 3', 'Luigi Verdi', null, null, null, null, null],
    ], null, 'A1');
    $s1 = $spreadsheet->createSheet();
    $s1->fromArray([['codice', 'nominativo', 'sezione', 'codice sezione', 'regione', 'provincia', 'email', 'indirizzo']], null, 'A1');

    $tmpPath = sys_get_temp_dir().'/prenotar_reduced_'.uniqid().'.xlsx';
    (new Xlsx($spreadsheet))->save($tmpPath);

    $result = $service->import($tmpPath, null, force: true);

    $alfaSezione = Sezione::where('codice', '9299901')->first();
    $alfaUser = User::where('codice_cai', '9299901')->first();
    expect($alfaSezione->is_active)->toBeFalse()
        ->and($alfaUser->is_active)->toBeFalse()
        ->and($result->userDisattivati)->toBeGreaterThanOrEqual(1);

    unlink($tmpPath);
});

it('sottosezione orfana viene loggata come errore e non creata', function (): void {
    $service = app(ExcelImportService::class);
    $result = $service->import(miniXlsxPath());

    // 9199999 → sezione padre 9299999 non esiste
    expect(Sottosezione::where('codice', '9199999')->exists())->toBeFalse()
        ->and($result->righeInErrore)->toBeGreaterThanOrEqual(1);

    $matchingLog = array_filter($result->log, fn ($entry) => str_contains($entry, '9199999'));
    expect($matchingLog)->not->toBeEmpty();
});

it('dispatcha UserSetPasswordRequested solo per i nuovi user', function (): void {
    $service = app(ExcelImportService::class);
    $service->import(miniXlsxPath());

    // 5 user nuovi (3 sezioni + 2 sottosezioni valide)
    Event::assertDispatchedTimes(UserSetPasswordRequested::class, 5);
});
