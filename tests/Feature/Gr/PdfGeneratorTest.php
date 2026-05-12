<?php

declare(strict_types=1);

use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use App\Services\PdfGenerator;
use App\Settings\GrSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre = Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
    $this->sezione = User::factory()->sezione()->create();
    $this->generator = app(PdfGenerator::class);
});

test('richiestaParete genera un PDF valido', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'nome_evento' => 'Evento Alpino',
        'tipo_evento' => 'Corso',
        'indirizzo_evento' => 'Via Roma 1, Como',
        'data_inizio_prenotazione' => today()->addDays(30)->toDateString(),
        'data_fine_prenotazione' => today()->addDays(35)->toDateString(),
        'data_inizio_evento' => today()->addDays(31)->toDateString(),
        'data_fine_evento' => today()->addDays(34)->toDateString(),
        'responsabile_nome' => 'Mario Bianchi',
        'responsabile_tipo' => 'istruttore',
        'responsabile_telefono' => '3331234567',
        'responsabile_email' => 'mario@test.it',
    ]);

    $pdf = $this->generator->richiestaParete($pren);
    $output = $pdf->output();

    expect($output)->toStartWith('%PDF-')
        ->and(strlen($output))->toBeGreaterThan(1000);
});

test('modulo3 genera un PDF valido con i dati del presidente', function (): void {
    $settings = app(GrSettings::class);
    $settings->presidente_nome = 'Giovanni Rossi';
    $settings->presidente_nato_a = 'Milano';
    $settings->presidente_data_nascita = '1960-05-15';
    $settings->save();

    $pren = Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'nome_evento' => 'Evento Alpino',
        'tipo_evento' => 'Corso',
        'indirizzo_evento' => 'Via Roma 1, Como',
        'data_inizio_prenotazione' => today()->addDays(30)->toDateString(),
        'data_fine_prenotazione' => today()->addDays(35)->toDateString(),
        'data_inizio_evento' => today()->addDays(31)->toDateString(),
        'data_fine_evento' => today()->addDays(34)->toDateString(),
        'responsabile_nome' => 'Mario Bianchi',
        'responsabile_tipo' => 'istruttore',
        'responsabile_telefono' => '3331234567',
        'responsabile_email' => 'mario@test.it',
    ]);

    $pdf = $this->generator->modulo3($pren, app(GrSettings::class));
    $output = $pdf->output();

    expect($output)->toStartWith('%PDF-')
        ->and(strlen($output))->toBeGreaterThan(1000);
});
