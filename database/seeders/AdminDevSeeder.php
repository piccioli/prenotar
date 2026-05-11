<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminDevSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $user = User::updateOrCreate(
            ['email' => 'admin@local.test'],
            [
                'name' => 'Admin Dev',
                'password' => Hash::make('password'),
                'email_is_fallback' => false,
                'is_active' => true,
            ]
        );

        $user->syncRoles(['admin']);
    }
}
