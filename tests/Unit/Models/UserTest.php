<?php

declare(strict_types=1);

use App\Models\User;

it('returns contact_email when set', function (): void {
    $user = new User([
        'email' => 'login@example.com',
        'contact_email' => 'real@example.com',
    ]);

    expect($user->effective_contact_email)->toBe('real@example.com');
});

it('falls back to email when contact_email is null', function (): void {
    $user = new User([
        'email' => 'login@example.com',
        'contact_email' => null,
    ]);

    expect($user->effective_contact_email)->toBe('login@example.com');
});
