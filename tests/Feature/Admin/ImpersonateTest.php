<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->sezione = User::factory()->sezione()->create();
});

test('canImpersonate ritorna true solo per admin', function (): void {
    expect($this->admin->canImpersonate())->toBeTrue()
        ->and($this->sezione->canImpersonate())->toBeFalse();
});

test('canBeImpersonated ritorna true per utenti non-admin attivi', function (): void {
    expect($this->sezione->canBeImpersonated())->toBeTrue()
        ->and($this->admin->canBeImpersonated())->toBeFalse();
});

test('utente disattivato non può essere impersonato', function (): void {
    $this->sezione->update(['is_active' => false]);
    expect($this->sezione->fresh()->canBeImpersonated())->toBeFalse();
});

test('audit log traccia impersonate begin e end', function (): void {
    $this->actingAs($this->admin);

    app(AuditLogger::class)->logAdminAction(
        'user.impersonate_begin',
        $this->sezione,
        'Impersonate avviato da admin',
        ['admin_id' => $this->admin->id, 'target_id' => $this->sezione->id],
    );

    app(AuditLogger::class)->logAdminAction(
        'user.impersonate_end',
        $this->sezione,
        'Impersonate terminato',
        ['admin_id' => $this->admin->id, 'target_id' => $this->sezione->id],
    );

    expect(Activity::where('event', 'user.impersonate_begin')->count())->toBe(1)
        ->and(Activity::where('event', 'user.impersonate_end')->count())->toBe(1);
});
