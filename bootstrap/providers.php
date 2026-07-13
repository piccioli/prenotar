<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\GrPanelProvider;
use App\Providers\Filament\LoginPanelProvider;
use App\Providers\Filament\SezionePanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    LoginPanelProvider::class,
    AdminPanelProvider::class,
    GrPanelProvider::class,
    SezionePanelProvider::class,
    HorizonServiceProvider::class,
];
