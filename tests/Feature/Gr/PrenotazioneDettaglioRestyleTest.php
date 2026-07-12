<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Filament\Gr\Resources\PrenotazioneResource\Pages\ViewPrenotazione;
use App\Models\Prenotazione;
use App\Models\Sezione;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('gr'));
    $this->gr = User::factory()->grManager()->create();
});

test('header mostra titolo evento, etichetta richiedente e badge stato', function (): void {
    $sezione = Sezione::factory()->create(['nominativo' => 'SEZIONE DI BERGAMO']);
    $pren = Prenotazione::factory()->inviata()->create([
        'sezione_id' => $sezione->id,
        'sottosezione_id' => null,
        'nome_evento' => 'Festa di primavera',
    ]);

    Livewire::actingAs($this->gr)
        ->test(ViewPrenotazione::class, ['record' => $pren->getKey()])
        ->assertSee('Festa di primavera')
        ->assertSee('Sezione di')
        ->assertSee('SEZIONE DI BERGAMO')
        ->assertSee('Inviata');
});

test('azione approva è visibile in stato Inviata e nascosta in stato Approvata', function (): void {
    $inviata = Prenotazione::factory()->inviata()->create();
    $approvata = Prenotazione::factory()->approvata()->create();

    Livewire::actingAs($this->gr)
        ->test(ViewPrenotazione::class, ['record' => $inviata->getKey()])
        ->assertInfolistActionVisible('.approvaAction', 'approva')
        ->assertInfolistActionVisible('.rifiutaAction', 'rifiuta');

    Livewire::actingAs($this->gr)
        ->test(ViewPrenotazione::class, ['record' => $approvata->getKey()])
        ->assertDontSee('Approva richiesta')
        ->assertDontSee('Rifiuta — motivo obbligatorio');
});

test('azione approva eseguita transiziona la prenotazione ad Approvata', function (): void {
    $pren = Prenotazione::factory()->inviata()->create();

    Livewire::actingAs($this->gr)
        ->test(ViewPrenotazione::class, ['record' => $pren->getKey()])
        ->callInfolistAction('.approvaAction', 'approva', []);

    expect($pren->fresh()->status)->toBe(PrenotazioneStatus::Approvata);
});

test('sezione documenti (download PDF) è visibile solo da Approvata in poi', function (): void {
    $inviata = Prenotazione::factory()->inviata()->create();
    $approvata = Prenotazione::factory()->approvata()->create();

    Livewire::actingAs($this->gr)
        ->test(ViewPrenotazione::class, ['record' => $inviata->getKey()])
        ->assertDontSee('Scarica Richiesta parete');

    Livewire::actingAs($this->gr)
        ->test(ViewPrenotazione::class, ['record' => $approvata->getKey()])
        ->assertInfolistActionVisible('.download_richiestaAction', 'download_richiesta')
        ->assertInfolistActionVisible('.download_modulo3Action', 'download_modulo3');
});

test('tab Allegati mostra il conteggio dei documenti effettivamente caricati', function (): void {
    $pren = Prenotazione::factory()->inviata()->create();
    $pren->addMedia(UploadedFile::fake()->image('delibera.jpg', 10, 10))->toMediaCollection('delibera_consiglio');
    $pren->addMedia(UploadedFile::fake()->image('ztl.jpg', 10, 10))->toMediaCollection('autorizzazione_ztl');

    Livewire::actingAs($this->gr)
        ->test(ViewPrenotazione::class, ['record' => $pren->getKey()])
        ->assertSee('Allegati')
        ->assertSee('2');
});
