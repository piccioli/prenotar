<?php

declare(strict_types=1);

use App\Models\Prenotazione;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ── sezione: propria bozza ──────────────────────────────────────────────────

it('sezione owner can update own bozza', function (): void {
    $user = User::factory()->sezione()->create();
    $prenotazione = Prenotazione::factory()->create(['user_id' => $user->id]);

    expect($user->can('update', $prenotazione))->toBeTrue();
});

it('sezione owner can delete own bozza', function (): void {
    $user = User::factory()->sezione()->create();
    $prenotazione = Prenotazione::factory()->create(['user_id' => $user->id]);

    expect($user->can('delete', $prenotazione))->toBeTrue();
});

// ── sezione: bozza altrui ──────────────────────────────────────────────────

it('sezione cannot update another user bozza', function (): void {
    $owner = User::factory()->sezione()->create();
    $other = User::factory()->sezione()->create();
    $prenotazione = Prenotazione::factory()->create(['user_id' => $owner->id]);

    expect($other->can('update', $prenotazione))->toBeFalse();
});

it('sezione cannot delete another user bozza', function (): void {
    $owner = User::factory()->sezione()->create();
    $other = User::factory()->sezione()->create();
    $prenotazione = Prenotazione::factory()->create(['user_id' => $owner->id]);

    expect($other->can('delete', $prenotazione))->toBeFalse();
});

// ── sezione: propria non-bozza ─────────────────────────────────────────────

it('sezione cannot update own inviata prenotazione', function (): void {
    $user = User::factory()->sezione()->create();
    $prenotazione = Prenotazione::factory()->inviata()->create(['user_id' => $user->id]);

    expect($user->can('update', $prenotazione))->toBeFalse();
});

// ── gr_manager ─────────────────────────────────────────────────────────────

it('gr_manager cannot create prenotazione', function (): void {
    $gr = User::factory()->grManager()->create();

    expect($gr->can('create', Prenotazione::class))->toBeFalse();
});

it('gr_manager cannot update prenotazione', function (): void {
    $gr = User::factory()->grManager()->create();
    $prenotazione = Prenotazione::factory()->create();

    expect($gr->can('update', $prenotazione))->toBeFalse();
});

it('gr_manager can approve prenotazione', function (): void {
    $gr = User::factory()->grManager()->create();
    $prenotazione = Prenotazione::factory()->create();

    expect($gr->can('approve', $prenotazione))->toBeTrue();
});

// ── admin ──────────────────────────────────────────────────────────────────

it('admin cannot approve prenotazione', function (): void {
    $admin = User::factory()->admin()->create();
    $prenotazione = Prenotazione::factory()->create();

    expect($admin->can('approve', $prenotazione))->toBeFalse();
});

it('admin can forceState on prenotazione', function (): void {
    $admin = User::factory()->admin()->create();
    $prenotazione = Prenotazione::factory()->create();

    expect($admin->can('forceState', $prenotazione))->toBeTrue();
});

it('admin can hardDelete prenotazione', function (): void {
    $admin = User::factory()->admin()->create();
    $prenotazione = Prenotazione::factory()->create();

    expect($admin->can('hardDelete', $prenotazione))->toBeTrue();
});
