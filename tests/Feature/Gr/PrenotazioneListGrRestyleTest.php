<?php

declare(strict_types=1);

use App\Filament\Gr\Resources\PrenotazioneResource\Pages\ListPrenotazioni;
use App\Models\Prenotazione;
use App\Models\Sezione;
use App\Models\Sottosezione;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('gr'));
    $this->gr = User::factory()->grManager()->create();
});

test('la colonna Richiedente mostra "Sezione di X" per una richiesta di sezione', function (): void {
    $sezione = Sezione::factory()->create(['nominativo' => 'SEZIONE DI BERGAMO']);
    Prenotazione::factory()->inviata()->create([
        'sezione_id' => $sezione->id,
        'sottosezione_id' => null,
    ]);

    Livewire::actingAs($this->gr)
        ->test(ListPrenotazioni::class)
        ->assertSee('Sezione di')
        ->assertSee('SEZIONE DI BERGAMO');
});

test('la colonna Richiedente mostra l\'etichetta S.SEZ. per una richiesta di sottosezione', function (): void {
    $sezioneMadre = Sezione::factory()->create(['nominativo' => 'SEZ. VALTELLINESE-SONDRIO']);
    $sottosezione = Sottosezione::factory()->create([
        'sezione_id' => $sezioneMadre->id,
        'nominativo' => 'S.SEZ. TEGLIO',
    ]);
    Prenotazione::factory()->inviata()->create([
        'sezione_id' => null,
        'sottosezione_id' => $sottosezione->id,
    ]);

    Livewire::actingAs($this->gr)
        ->test(ListPrenotazioni::class)
        ->assertSee('S.SEZ. TEGLIO')
        ->assertSee('sez. rif. SEZ. VALTELLINESE-SONDRIO')
        ->assertDontSee('Sezione di S.SEZ. TEGLIO');
});

test('la colonna Periodo formatta un intervallo e una data singola', function (): void {
    Prenotazione::factory()->inviata()->create([
        'data_inizio_prenotazione' => '2026-09-19',
        'data_fine_prenotazione' => '2026-09-21',
    ]);
    Prenotazione::factory()->inviata()->create([
        'data_inizio_prenotazione' => '2026-09-12',
        'data_fine_prenotazione' => '2026-09-12',
    ]);

    Livewire::actingAs($this->gr)
        ->test(ListPrenotazioni::class)
        ->assertSee('19 set – 21 set 2026')
        ->assertSee('12 set 2026');
});
