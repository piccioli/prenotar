<?php

declare(strict_types=1);

use App\Models\Torre;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

test('AuditLogger crea record activity con event e motivo', function (): void {
    $this->actingAs($this->admin);
    $torre = Torre::factory()->create(['nome' => 'Torre Test']);

    app(AuditLogger::class)->logAdminAction(
        'torre.update',
        $torre,
        'Aggiornamento dati torre',
        ['campo' => 'nome', 'nuovo_valore' => 'Torre Test'],
    );

    $log = Activity::where('log_name', 'admin')->where('event', 'torre.update')->first();
    expect($log)->not->toBeNull()
        ->and($log->properties['motivo'])->toBe('Aggiornamento dati torre')
        ->and($log->subject_id)->toBe($torre->id)
        ->and($log->subject_type)->toBe(Torre::class);
});

test('AuditLogger senza subject non lancia eccezioni', function (): void {
    $this->actingAs($this->admin);

    expect(function (): void {
        app(AuditLogger::class)->logAdminAction('email.test', null, 'Test email', ['to' => 'test@example.com']);
    })->not->toThrow(Exception::class);

    $log = Activity::where('event', 'email.test')->first();
    expect($log)->not->toBeNull();
});

test('LogsActivity su Torre crea log automatico su update', function (): void {
    $torre = Torre::factory()->create(['nome' => 'Torre Originale']);

    $torre->update(['nome' => 'Torre Aggiornata']);

    $log = Activity::where('log_name', 'torre')->where('event', 'updated')->first();
    expect($log)->not->toBeNull()
        ->and($log->properties['attributes']['nome'])->toBe('Torre Aggiornata')
        ->and($log->properties['old']['nome'])->toBe('Torre Originale');
});

test('LogsActivity su User crea log automatico su update', function (): void {
    $user = User::factory()->sezione()->create(['name' => 'Nome Originale']);

    $user->update(['name' => 'Nome Aggiornato']);

    $log = Activity::where('log_name', 'user')->where('event', 'updated')->first();
    expect($log)->not->toBeNull()
        ->and($log->properties['attributes']['name'])->toBe('Nome Aggiornato');
});
