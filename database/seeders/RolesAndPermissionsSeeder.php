<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'prenotazioni' => [
            'view-any', 'view', 'create', 'update', 'delete',
            'approve', 'reject', 'change-dates', 'reassign-torre',
            'generate-pdf-richiesta', 'generate-pdf-modulo3',
            'send-insurance', 'mark-concluso',
            'force-state', 'hard-delete',
        ],
        'users' => [
            'view-any', 'view', 'create', 'update', 'delete',
            'impersonate', 'assign-role', 'reset-password',
        ],
        'sezioni' => ['view-any', 'view', 'sync-excel'],
        'sottosezioni' => ['view-any', 'view'],
        'torri' => ['view-any', 'view', 'create', 'update', 'delete'],
        'gr-settings' => ['view', 'update'],
        'audit-log' => ['view'],
    ];

    private const ROLE_PERMISSIONS = [
        'admin' => [
            'prenotazioni:view-any', 'prenotazioni:view',
            'prenotazioni:force-state', 'prenotazioni:hard-delete',
            'users:view-any', 'users:view', 'users:create', 'users:update', 'users:delete',
            'users:impersonate', 'users:assign-role', 'users:reset-password',
            'sezioni:view-any', 'sezioni:view', 'sezioni:sync-excel',
            'sottosezioni:view-any', 'sottosezioni:view',
            'torri:view-any', 'torri:view', 'torri:create', 'torri:update', 'torri:delete',
            'audit-log:view',
        ],
        'gr_manager' => [
            'prenotazioni:view-any', 'prenotazioni:view',
            'prenotazioni:approve', 'prenotazioni:reject',
            'prenotazioni:change-dates', 'prenotazioni:reassign-torre',
            'prenotazioni:generate-pdf-richiesta', 'prenotazioni:generate-pdf-modulo3',
            'prenotazioni:send-insurance', 'prenotazioni:mark-concluso',
            'sezioni:view-any', 'sezioni:view',
            'sottosezioni:view-any', 'sottosezioni:view',
            'torri:view-any', 'torri:view',
            'gr-settings:view', 'gr-settings:update',
        ],
        'sezione' => [
            'prenotazioni:view-any', 'prenotazioni:view',
            'prenotazioni:create', 'prenotazioni:update', 'prenotazioni:delete',
            'sezioni:view',
            'sottosezioni:view',
            'torri:view-any', 'torri:view',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$resource}:{$action}", 'guard_name' => 'web'],
                );
            }
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissionNames);
        }
    }
}
