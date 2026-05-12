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
});

test('test email crea audit log con destinatario', function (): void {
    $this->actingAs($this->admin);

    app(AuditLogger::class)->logAdminAction(
        'email.test',
        null,
        'Test invio email da admin',
        ['to' => 'dest@example.com', 'subject' => 'Test'],
    );

    $log = Activity::where('event', 'email.test')->first();
    expect($log)->not->toBeNull()
        ->and($log->properties['to'])->toBe('dest@example.com')
        ->and($log->causer_id)->toBe($this->admin->id);
});
