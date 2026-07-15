<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Resources\PrenotazioneResource\Pages\EditPrenotazione;
use App\Models\Prenotazione;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->sezione()->create();
    Filament::setCurrentPanel(Filament::getPanel('sezione'));
});

test('azione invia richiesta è disabilitata finché manca la delibera', function (): void {
    actingAs($this->user);
    $pren = Prenotazione::factory()->create([
        'user_id' => $this->user->id,
        'status' => PrenotazioneStatus::Bozza,
    ]);

    Livewire::test(EditPrenotazione::class, ['record' => $pren->getKey()])
        ->assertFormComponentActionDisabled('invia_richiestaAction', 'invia_richiesta');
});

test('azione invia richiesta è abilitata ed esegue la transizione quando la delibera è presente', function (): void {
    actingAs($this->user);
    $pren = Prenotazione::factory()->create([
        'user_id' => $this->user->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);
    $pren->addMedia(UploadedFile::fake()->image('delibera.jpg', 10, 10))
        ->toMediaCollection('delibera_consiglio');

    Livewire::test(EditPrenotazione::class, ['record' => $pren->getKey()])
        ->assertFormComponentActionEnabled('invia_richiestaAction', 'invia_richiesta')
        ->callFormComponentAction('invia_richiestaAction', 'invia_richiesta');

    expect($pren->fresh()->status)->toBe(PrenotazioneStatus::Inviata);
});

test('azione elimina bozza elimina la prenotazione', function (): void {
    actingAs($this->user);
    $pren = Prenotazione::factory()->create([
        'user_id' => $this->user->id,
        'status' => PrenotazioneStatus::Bozza,
    ]);
    $id = $pren->id;

    Livewire::test(EditPrenotazione::class, ['record' => $pren->getKey()])
        ->callFormComponentAction('elimina_bozzaAction', 'elimina_bozza');

    expect(Prenotazione::find($id))->toBeNull();
});
