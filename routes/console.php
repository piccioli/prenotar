<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('prenotazioni:nightly')
    ->dailyAt('05:00')
    ->timezone('Europe/Rome')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes();
