<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use App\Rules\NoOverlapTorre;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre = Torre::factory()->create(['is_active' => true]);
});

function makeRule(?int $torreId, string $inizio, string $fine, ?int $exclude = null): NoOverlapTorre
{
    return new NoOverlapTorre($torreId, $inizio, $fine, $exclude);
}

function validate(NoOverlapTorre $rule): bool
{
    $result = Validator::make(['torre_id' => 'value'], ['torre_id' => [$rule]]);

    return $result->passes();
}

test('inviata blocca la sovrapposizione sullo stesso periodo e torre', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->inviata()->create([
        'user_id' => $user->id,
        'torre_id' => $this->torre->id,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);

    $rule = makeRule($this->torre->id, '2027-10-11', '2027-10-13');
    expect(validate($rule))->toBeFalse();
});

test('bozza non blocca la sovrapposizione', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->create([
        'user_id' => $user->id,
        'torre_id' => $this->torre->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);

    $rule = makeRule($this->torre->id, '2027-10-11', '2027-10-13');
    expect(validate($rule))->toBeTrue();
});

test('annullata non blocca la sovrapposizione', function (): void {
    Prenotazione::factory()->annullata()->create([
        'torre_id' => $this->torre->id,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);

    $rule = makeRule($this->torre->id, '2027-10-11', '2027-10-13');
    expect(validate($rule))->toBeTrue();
});

test('torre null passa sempre', function (): void {
    Prenotazione::factory()->inviata()->create([
        'torre_id' => $this->torre->id,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);

    $rule = makeRule(null, '2027-10-11', '2027-10-13');
    expect(validate($rule))->toBeTrue();
});

test('il blocco è per torre non cross-torre', function (): void {
    $torre2 = Torre::factory()->create(['is_active' => true]);
    Prenotazione::factory()->inviata()->create([
        'torre_id' => $this->torre->id,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);

    $rule = makeRule($torre2->id, '2027-10-11', '2027-10-13');
    expect(validate($rule))->toBeTrue();
});

test('exclude esclude la stessa prenotazione in edit', function (): void {
    $pren = Prenotazione::factory()->inviata()->create([
        'torre_id' => $this->torre->id,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);

    $rule = makeRule($this->torre->id, '2027-10-10', '2027-10-12', $pren->id);
    expect(validate($rule))->toBeTrue();
});
