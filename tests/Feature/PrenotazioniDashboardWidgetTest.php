<?php

declare(strict_types=1);

use App\Filament\Sezione\Widgets\PrenotazioniDashboardWidget;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use App\Settings\GrSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('sezione'));
});

test('mostra lo stato vuoto quando la sezione non ha prenotazioni attive', function (): void {
    $user = User::factory()->sezione()->create();

    Livewire::actingAs($user)
        ->test(PrenotazioniDashboardWidget::class)
        ->assertSee('Nessuna prenotazione attiva')
        ->assertSee('Nuova prenotazione');
});

test('mostra la card prenotazione attiva con torre, deposito e badge stato', function (): void {
    $torre = Torre::factory()->create([
        'colore_hex' => '#1D574B',
        'indirizzo_deposito' => 'Via delle Industrie 12, Zanica (BG)',
    ]);
    $user = User::factory()->sezione()->create();

    $attiva = Prenotazione::factory()->inviata()->create([
        'user_id' => $user->id,
        'torre_id' => $torre->id,
        'nome_evento' => 'Festa dello Sport — Bergamo',
    ]);

    Livewire::actingAs($user)
        ->test(PrenotazioniDashboardWidget::class)
        ->assertSee('Festa dello Sport — Bergamo')
        ->assertSee($torre->nome)
        ->assertSee('Via delle Industrie 12, Zanica (BG)')
        ->assertSee($attiva->status->label())
        ->assertDontSee('Nessuna prenotazione attiva');
});

test('mostra avviso pdf firmato quando la prenotazione e approvata e senza pdf caricato', function (): void {
    $user = User::factory()->sezione()->create();
    $soglia = app(GrSettings::class)->giorni_minimi_caricamento_documenti;

    $attiva = Prenotazione::factory()->approvata()->create([
        'user_id' => $user->id,
        'pdf_firmato_at' => null,
    ]);

    $scadenzaAttesa = $attiva->data_inizio_evento->copy()->subDays($soglia);

    Livewire::actingAs($user)
        ->test(PrenotazioniDashboardWidget::class)
        ->assertSee('Prossimo passo')
        ->assertSee($scadenzaAttesa->translatedFormat('d MMMM Y'));
});

test('non mostra avviso pdf firmato quando il pdf e gia stato caricato', function (): void {
    $user = User::factory()->sezione()->create();

    $attiva = Prenotazione::factory()->approvata()->create([
        'user_id' => $user->id,
        'pdf_firmato_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(PrenotazioniDashboardWidget::class)
        ->assertSee($attiva->nome_evento)
        ->assertDontSee('Prossimo passo');
});
