<?php

declare(strict_types=1);

use App\Filament\Gr\Pages\ImpostazioniPage;
use App\Models\User;
use App\Settings\GrSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->gr = User::factory()->grManager()->create();
    $this->sezione = User::factory()->sezione()->create();
});

test('gr_manager può accedere alla pagina impostazioni', function (): void {
    $this->actingAs($this->gr)
        ->get(ImpostazioniPage::getUrl(panel: 'gr'))
        ->assertSuccessful();
});

test('utente sezione non può accedere alla pagina impostazioni GR', function (): void {
    $this->actingAs($this->sezione)
        ->get(ImpostazioniPage::getUrl(panel: 'gr'))
        ->assertForbidden();
});

test('admin non può accedere alla pagina impostazioni GR', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->get(ImpostazioniPage::getUrl(panel: 'gr'))
        ->assertForbidden();
});

test('il form mostra i valori correnti di GrSettings', function (): void {
    $settings = app(GrSettings::class);
    $settings->presidente_nome = 'Mario Rossi';
    $settings->giorni_minimi_caricamento_documenti = 14;
    $settings->save();

    $this->actingAs($this->gr);

    Livewire::test(ImpostazioniPage::class)
        ->assertSet('data.presidente_nome', 'Mario Rossi')
        ->assertSet('data.giorni_minimi_caricamento_documenti', 14);
});

test('il submit aggiorna i giorni minimi in GrSettings', function (): void {
    $this->actingAs($this->gr);

    Livewire::test(ImpostazioniPage::class)
        ->set('data.giorni_minimi_caricamento_documenti', 20)
        ->set('data.ore_minime_richiesta_assicurazione', 72)
        ->call('save');

    $reloaded = app(GrSettings::class);
    expect($reloaded->giorni_minimi_caricamento_documenti)->toBe(20)
        ->and($reloaded->ore_minime_richiesta_assicurazione)->toBe(72);
});

test('il submit aggiorna le email notifiche GR', function (): void {
    $this->actingAs($this->gr);

    Livewire::test(ImpostazioniPage::class)
        ->set('data.emails_notifiche_gr', ['presidente@cai.it', 'segreteria@cai.it'])
        ->call('save');

    $reloaded = app(GrSettings::class);
    expect($reloaded->emails_notifiche_gr)
        ->toContain('presidente@cai.it')
        ->toContain('segreteria@cai.it');
});

test('il submit aggiorna l\'anagrafica del presidente GR', function (): void {
    $this->actingAs($this->gr);

    Livewire::test(ImpostazioniPage::class)
        ->set('data.presidente_nome', 'Giovanni Bianchi')
        ->set('data.presidente_nato_a', 'Milano')
        ->set('data.presidente_data_nascita', '1960-05-15')
        ->call('save');

    $reloaded = app(GrSettings::class);
    expect($reloaded->presidente_nome)->toBe('Giovanni Bianchi')
        ->and($reloaded->presidente_nato_a)->toBe('Milano')
        ->and($reloaded->presidente_data_nascita)->toBe('1960-05-15');
});

test('save invia notifica di successo', function (): void {
    $this->actingAs($this->gr);

    Livewire::test(ImpostazioniPage::class)
        ->call('save')
        ->assertNotified('Impostazioni salvate');
});
