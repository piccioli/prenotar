<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('admin accede a /admin e viene bloccato da /gr e /sezione', function (): void {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();

    $this->actingAs($user)
        ->get('/gr')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/sezione')
        ->assertForbidden();
});

it('gr_manager accede a /gr e viene bloccato da /admin e /sezione', function (): void {
    $user = User::factory()->grManager()->create();

    $this->actingAs($user)
        ->get('/gr')
        ->assertOk();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/sezione')
        ->assertForbidden();
});

it('sezione accede a /sezione e viene bloccato da /admin e /gr', function (): void {
    $user = User::factory()->sezione()->create();

    $this->actingAs($user)
        ->get('/sezione')
        ->assertOk();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/gr')
        ->assertForbidden();
});

it('user con is_active=false non può accedere a nessun pannello', function (): void {
    $user = User::factory()->sezione()->create(['is_active' => false]);

    $this->actingAs($user)
        ->get('/sezione')
        ->assertForbidden();
});
