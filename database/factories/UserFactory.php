<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sezione;
use App\Models\Sottosezione;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'codice_cai' => null,
            'sezione_id' => null,
            'sottosezione_id' => null,
            'contact_email' => null,
            'email_is_fallback' => false,
            'is_active' => true,
            'last_login_at' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('admin'));
    }

    public function grManager(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('gr_manager'));
    }

    public function sezione(?Sezione $sezione = null): static
    {
        return $this
            ->state(fn (array $attributes) => [
                'sezione_id' => $sezione !== null ? $sezione->id : Sezione::factory(),
                'sottosezione_id' => null,
                'codice_cai' => (string) fake()->unique()->numberBetween(9200000, 9299999),
            ])
            ->afterCreating(fn (User $user) => $user->assignRole('sezione'));
    }

    public function sottosezione(?Sottosezione $sottosezione = null): static
    {
        return $this
            ->state(fn (array $attributes) => [
                'sezione_id' => null,
                'sottosezione_id' => $sottosezione !== null ? $sottosezione->id : Sottosezione::factory(),
                'codice_cai' => (string) fake()->unique()->numberBetween(9100000, 9199999),
            ])
            ->afterCreating(fn (User $user) => $user->assignRole('sezione'));
    }

    public function withFallbackEmail(): static
    {
        return $this->state(function (array $attributes) {
            $codice = $attributes['codice_cai'] ?? (string) fake()->unique()->numberBetween(9200000, 9299999);

            return [
                'codice_cai' => $codice,
                'email' => "{$codice}@grlomct.it",
                'email_is_fallback' => true,
            ];
        });
    }

    public function withContactEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'contact_email' => $email,
        ]);
    }
}
