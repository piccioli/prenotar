<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

it('serves the welcome page', function () {
    $this->get('/')->assertStatus(200);
});

it('connects to the database', function () {
    expect(fn () => DB::connection()->getPdo())->not->toThrow(Exception::class);
});

it('can queue a mail without errors', function () {
    Mail::fake();

    Mail::raw('ping', fn ($m) => $m->to('test@example.com')->subject('ping'));

    expect(true)->toBeTrue();
});
