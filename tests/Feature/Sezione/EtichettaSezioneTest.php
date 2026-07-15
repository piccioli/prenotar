<?php

declare(strict_types=1);

use App\Filament\Sezione\Resources\PrenotazioneResource\Pages\ListPrenotazioni;
use App\Filament\Sezione\Resources\PrenotazioneResource\Pages\ViewPrenotazione;
use App\Filament\Sezione\Widgets\PrenotazioniDashboardWidget;
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
    Filament::setCurrentPanel(Filament::getPanel('sezione'));
});

test('un utente sezione vede solo il nome della sezione, senza riferimento a "sez. rif."', function (): void {
    $sezione = Sezione::factory()->create(['nominativo' => 'SEZ. BERGAMO']);
    $user = User::factory()->sezione($sezione)->create();

    Livewire::actingAs($user)
        ->test(PrenotazioniDashboardWidget::class)
        ->assertSee('SEZ. BERGAMO')
        ->assertDontSee('sez. rif.');
});

test('un utente sottosezione vede l\'etichetta S.SEZ. con riferimento alla sezione madre in dashboard, lista e dettaglio', function (): void {
    $sezione = Sezione::factory()->create(['nominativo' => 'SEZ. VALTELLINESE-SONDRIO']);
    $sottosezione = Sottosezione::factory()->create([
        'sezione_id' => $sezione->id,
        'nominativo' => 'S.SEZ. TEGLIO (diventata Sezione)',
    ]);
    $user = User::factory()->sottosezione($sottosezione)->create();

    Livewire::actingAs($user)
        ->test(PrenotazioniDashboardWidget::class)
        ->assertSee('S.SEZ. TEGLIO (diventata Sezione)')
        ->assertSee('sez. rif. SEZ. VALTELLINESE-SONDRIO');

    Livewire::actingAs($user)
        ->test(ListPrenotazioni::class)
        ->assertSee('S.SEZ. TEGLIO (diventata Sezione)')
        ->assertSee('sez. rif. SEZ. VALTELLINESE-SONDRIO');

    $prenotazione = Prenotazione::factory()->create([
        'user_id' => $user->id,
        'sezione_id' => null,
        'sottosezione_id' => $sottosezione->id,
    ]);

    Livewire::actingAs($user)
        ->test(ViewPrenotazione::class, ['record' => $prenotazione->getKey()])
        ->assertSee('S.SEZ. TEGLIO (diventata Sezione)')
        ->assertSee('sez. rif. SEZ. VALTELLINESE-SONDRIO');
});
