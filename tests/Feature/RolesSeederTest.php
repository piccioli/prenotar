<?php

declare(strict_types=1);

use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates exactly 3 roles', function (): void {
    expect(Role::count())->toBe(3);
    expect(Role::pluck('name')->sort()->values()->toArray())
        ->toBe(['admin', 'gr_manager', 'sezione']);
});

it('admin has no prenotazioni:create permission', function (): void {
    $admin = Role::findByName('admin');
    expect($admin->hasPermissionTo('prenotazioni:create'))->toBeFalse();
});

it('admin has prenotazioni:hard-delete permission', function (): void {
    $admin = Role::findByName('admin');
    expect($admin->hasPermissionTo('prenotazioni:hard-delete'))->toBeTrue();
});

it('gr_manager has prenotazioni:approve permission', function (): void {
    $gr = Role::findByName('gr_manager');
    expect($gr->hasPermissionTo('prenotazioni:approve'))->toBeTrue();
});

it('gr_manager has no prenotazioni:create permission', function (): void {
    $gr = Role::findByName('gr_manager');
    expect($gr->hasPermissionTo('prenotazioni:create'))->toBeFalse();
});

it('gr_manager has no users:view permission', function (): void {
    $gr = Role::findByName('gr_manager');
    expect($gr->hasPermissionTo('users:view'))->toBeFalse();
});

it('sezione has prenotazioni:create permission', function (): void {
    $sezione = Role::findByName('sezione');
    expect($sezione->hasPermissionTo('prenotazioni:create'))->toBeTrue();
});

it('sezione has no prenotazioni:approve permission', function (): void {
    $sezione = Role::findByName('sezione');
    expect($sezione->hasPermissionTo('prenotazioni:approve'))->toBeFalse();
});

it('seeder is idempotent', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    expect(Role::count())->toBe(3);
});
