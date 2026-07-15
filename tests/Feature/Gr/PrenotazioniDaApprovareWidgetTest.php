<?php

declare(strict_types=1);

use App\Filament\Gr\Widgets\PrenotazioniDaApprovareWidget;
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

test('conta correttamente le richieste da approvare e quelle approvate nei prossimi 30 giorni', function (): void {
    Prenotazione::factory()->inviata()->count(2)->create();
    Prenotazione::factory()->approvata()->create([
        'data_inizio_prenotazione' => now()->addDays(10),
        'data_fine_prenotazione' => now()->addDays(12),
    ]);
    Prenotazione::factory()->approvata()->create([
        'data_inizio_prenotazione' => now()->addDays(90),
        'data_fine_prenotazione' => now()->addDays(92),
    ]);

    $widget = new PrenotazioniDaApprovareWidget;

    expect($widget->getCountDaApprovare())->toBe(2)
        ->and($widget->getCountApprovateProssimi30Giorni())->toBe(1);
});

test('giorniMancanti e isUrgente riflettono la distanza dalla data di inizio prenotazione', function (): void {
    $vicina = Prenotazione::factory()->inviata()->create([
        'data_inizio_prenotazione' => now()->addDays(15)->startOfDay(),
        'data_fine_prenotazione' => now()->addDays(16)->startOfDay(),
    ]);
    $lontana = Prenotazione::factory()->inviata()->create([
        'data_inizio_prenotazione' => now()->addDays(45)->startOfDay(),
        'data_fine_prenotazione' => now()->addDays(46)->startOfDay(),
    ]);

    $widget = new PrenotazioniDaApprovareWidget;

    expect($widget->giorniMancanti($vicina))->toBe(15)
        ->and($widget->isUrgente($vicina))->toBeTrue()
        ->and($widget->giorniMancanti($lontana))->toBe(45)
        ->and($widget->isUrgente($lontana))->toBeFalse();
});

test('la lista mostra il badge "tra N giorni" e l\'etichetta S.SEZ. per una richiesta di sottosezione', function (): void {
    $sezioneMadre = Sezione::factory()->create(['nominativo' => 'SEZ. VALTELLINESE-SONDRIO']);
    $sottosezione = Sottosezione::factory()->create([
        'sezione_id' => $sezioneMadre->id,
        'nominativo' => 'S.SEZ. TEGLIO (diventata Sezione)',
    ]);
    Prenotazione::factory()->inviata()->create([
        'sezione_id' => null,
        'sottosezione_id' => $sottosezione->id,
        'data_inizio_prenotazione' => now()->addDays(12)->startOfDay(),
        'data_fine_prenotazione' => now()->addDays(13)->startOfDay(),
    ]);

    Livewire::actingAs($this->gr)
        ->test(PrenotazioniDaApprovareWidget::class)
        ->assertSee('tra 12 giorni')
        ->assertSee('S.SEZ. TEGLIO (diventata Sezione)')
        ->assertSee('sez. rif. SEZ. VALTELLINESE-SONDRIO');
});

test('la lista mostra "Da assegnare" quando la richiesta non ha ancora una torre', function (): void {
    Prenotazione::factory()->inviata()->create(['torre_id' => null]);

    Livewire::actingAs($this->gr)
        ->test(PrenotazioniDaApprovareWidget::class)
        ->assertSee('Da assegnare');
});

test('mostra lo stato vuoto "Tutto approvato" quando non ci sono richieste da approvare', function (): void {
    Livewire::actingAs($this->gr)
        ->test(PrenotazioniDaApprovareWidget::class)
        ->assertSee('Tutto approvato')
        ->assertDontSee('Richieste in attesa — le più urgenti in cima');
});
