<?php

declare(strict_types=1);

use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Models\Sottosezione;
use App\Models\Torre;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Torre::factory()->create(['is_active' => true]);
});

test('utente con prenotazione attiva non accede al wizard e vede il blocco', function (): void {
    $user = User::factory()->sezione()->create();
    $prenotazione = Prenotazione::factory()->inviata()->create([
        'user_id' => $user->id,
        'sezione_id' => $user->sezione_id,
        'nome_evento' => 'Corso Roccia 2026',
    ]);

    actingAs($user)
        ->get(PrenotazioneResource::getUrl('create', panel: 'sezione'))
        ->assertSuccessful()
        ->assertSee('Hai già una prenotazione attiva')
        ->assertSee('Corso Roccia 2026')
        ->assertSee(PrenotazioneResource::getUrl('view', ['record' => $prenotazione]), escape: false)
        ->assertDontSee('Quando & dove');
});

test('utente con prenotazione approvata della propria sottosezione non accede al wizard', function (): void {
    $sottosezione = Sottosezione::factory()->create();
    $user = User::factory()->sottosezione($sottosezione)->create();
    Prenotazione::factory()->approvata()->create([
        'user_id' => $user->id,
        'sezione_id' => null,
        'sottosezione_id' => $sottosezione->id,
    ]);

    actingAs($user)
        ->get(PrenotazioneResource::getUrl('create', panel: 'sezione'))
        ->assertSuccessful()
        ->assertSee('Hai già una prenotazione attiva')
        ->assertDontSee('Quando & dove');
});

test('utente senza prenotazioni attive accede normalmente al wizard', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->annullata()->create([
        'user_id' => $user->id,
        'sezione_id' => $user->sezione_id,
    ]);

    actingAs($user)
        ->get(PrenotazioneResource::getUrl('create', panel: 'sezione'))
        ->assertSuccessful()
        ->assertSee('Quando & dove')
        ->assertDontSee('Hai già una prenotazione attiva');
});
