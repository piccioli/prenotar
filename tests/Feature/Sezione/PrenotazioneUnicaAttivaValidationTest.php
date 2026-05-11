<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use App\Rules\UnicaPrenotazioneAttivaPerUser;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Torre::factory()->create(['is_active' => true]);
});

function validateUnica(User $user, ?int $exclude = null): bool
{
    $rule = new UnicaPrenotazioneAttivaPerUser($user, $exclude);

    return Validator::make(['data' => 'value'], ['data' => [$rule]])->passes();
}

test('utente con prenotazione Inviata non può crearne un\'altra', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->inviata()->create(['user_id' => $user->id]);

    expect(validateUnica($user))->toBeFalse();
});

test('utente con prenotazione Approvata non può crearne un\'altra', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->approvata()->create(['user_id' => $user->id]);

    expect(validateUnica($user))->toBeFalse();
});

test('utente con sola Bozza può creare una nuova prenotazione', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->create(['user_id' => $user->id, 'status' => PrenotazioneStatus::Bozza]);

    expect(validateUnica($user))->toBeTrue();
});

test('utente con Concluso può creare una nuova prenotazione', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->create(['user_id' => $user->id, 'status' => PrenotazioneStatus::Concluso]);

    expect(validateUnica($user))->toBeTrue();
});

test('utente con Annullata può creare una nuova prenotazione', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->annullata()->create(['user_id' => $user->id]);

    expect(validateUnica($user))->toBeTrue();
});

test('exclude permette di aggiornare la propria prenotazione attiva', function (): void {
    $user = User::factory()->sezione()->create();
    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $user->id]);

    expect(validateUnica($user, $pren->id))->toBeTrue();
});
