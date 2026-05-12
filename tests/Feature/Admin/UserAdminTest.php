<?php

declare(strict_types=1);

use App\Events\UserSetPasswordRequested;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->sezione = User::factory()->sezione()->create();
});

test('scopeActive filtra solo utenti attivi', function (): void {
    $this->sezione->update(['is_active' => false]);

    $attivi = User::active()->get();
    expect($attivi->contains($this->admin))->toBeTrue()
        ->and($attivi->contains($this->sezione))->toBeFalse();
});

test('reset password dispatcha evento e crea audit log', function (): void {
    Event::fake([UserSetPasswordRequested::class]);
    $this->actingAs($this->admin);

    event(new UserSetPasswordRequested($this->sezione));
    app(AuditLogger::class)->logAdminAction('user.reset_password', $this->sezione, 'Reset password da admin');

    Event::assertDispatched(UserSetPasswordRequested::class, fn ($e) => $e->user->id === $this->sezione->id);

    $log = Activity::where('event', 'user.reset_password')->first();
    expect($log)->not->toBeNull()
        ->and($log->properties['motivo'])->toBe('Reset password da admin');
});

test('toggle_active: disattiva utente e crea audit log', function (): void {
    $this->actingAs($this->admin);

    $this->sezione->update(['is_active' => false]);
    app(AuditLogger::class)->logAdminAction('user.toggle_active', $this->sezione, 'Test disattivazione', ['is_active' => false]);

    expect($this->sezione->fresh()->is_active)->toBeFalse();

    $log = Activity::where('event', 'user.toggle_active')->first();
    expect($log)->not->toBeNull()
        ->and($log->properties['is_active'])->toBeFalse();
});

test('non-admin non può accedere al pannello admin', function (): void {
    expect($this->sezione->canAccessPanel(
        app(Panel::class)->id('admin')
    ))->toBeFalse();
});
