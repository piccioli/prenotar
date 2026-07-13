<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('la pagina di login unica /login risponde 200 con branding CAI GR Lombardia', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('CAI GR Lombardia');
});

it('un admin gia autenticato che visita /login viene reindirizzato a /admin', function (): void {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect('/admin');
});

it('un gr_manager gia autenticato che visita /login viene reindirizzato a /gr', function (): void {
    $user = User::factory()->grManager()->create();

    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect('/gr');
});

it('una sezione gia autenticata che visita /login viene reindirizzata a /sezione', function (): void {
    $user = User::factory()->sezione()->create();

    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect('/sezione');
});
