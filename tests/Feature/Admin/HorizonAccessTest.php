<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin accede a /horizon', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/horizon')
        ->assertOk();
});

test('gr_manager non accede a /horizon', function (): void {
    $gr = User::factory()->grManager()->create();

    $this->actingAs($gr)
        ->get('/horizon')
        ->assertForbidden();
});

test('sezione non accede a /horizon', function (): void {
    $sezione = User::factory()->sezione()->create();

    $this->actingAs($sezione)
        ->get('/horizon')
        ->assertForbidden();
});

test('guest non autenticato riceve 403 da /horizon', function (): void {
    $this->get('/horizon')
        ->assertForbidden();
});
