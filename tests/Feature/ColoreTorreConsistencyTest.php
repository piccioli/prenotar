<?php

declare(strict_types=1);

use App\Filament\Gr\Resources\PrenotazioneResource\Pages\ListPrenotazioni as GrListPrenotazioni;
use App\Filament\Sezione\Resources\PrenotazioneResource\Pages\ListPrenotazioni as SezioneListPrenotazioni;
use App\Filament\Sezione\Widgets\CalendarioPrenotazioniWidget;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('il colore della torre nel calendario e nel badge tabella prenotazioni deriva sempre da colore_hex', function (): void {
    $torre = Torre::factory()->create(['colore_hex' => '#1D574B', 'is_active' => true]);
    $sezioneUser = User::factory()->sezione()->create();

    $prenotazione = Prenotazione::factory()->inviata()->create([
        'user_id' => $sezioneUser->id,
        'torre_id' => $torre->id,
        'data_inizio_prenotazione' => '2027-09-01',
        'data_fine_prenotazione' => '2027-09-05',
    ]);

    $widget = new CalendarioPrenotazioniWidget;
    $eventi = $widget->fetchEvents([
        'start' => '2027-09-01',
        'end' => '2027-09-30',
        'timezone' => 'UTC',
    ]);
    $evento = collect($eventi)->firstWhere('id', $prenotazione->id);

    expect($evento['backgroundColor'])->toBe($torre->colore_hex);

    Filament::setCurrentPanel(Filament::getPanel('gr'));
    $colonnaGr = Livewire::actingAs(User::factory()->grManager()->create())
        ->test(GrListPrenotazioni::class)
        ->instance()
        ->getTable()
        ->getColumn('torre.nome')
        ->record($prenotazione);

    Filament::setCurrentPanel(Filament::getPanel('sezione'));
    $colonnaSezione = Livewire::actingAs($sezioneUser)
        ->test(SezioneListPrenotazioni::class)
        ->instance()
        ->getTable()
        ->getColumn('torre.nome')
        ->record($prenotazione);

    $coloreAtteso = Color::hex($torre->colore_hex);

    expect($colonnaGr->getColor($prenotazione->torre->nome))->toBe($coloreAtteso)
        ->and($colonnaSezione->getColor($prenotazione->torre->nome))->toBe($coloreAtteso);
});
