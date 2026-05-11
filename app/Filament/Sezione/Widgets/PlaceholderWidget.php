<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Widgets;

use Filament\Widgets\Widget;

class PlaceholderWidget extends Widget
{
    protected static string $view = 'filament.sezione.widgets.placeholder';

    protected static bool $isLazy = false;
}
