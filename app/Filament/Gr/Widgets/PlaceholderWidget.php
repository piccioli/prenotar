<?php

declare(strict_types=1);

namespace App\Filament\Gr\Widgets;

use Filament\Widgets\Widget;

class PlaceholderWidget extends Widget
{
    protected static string $view = 'filament.gr.widgets.placeholder';

    protected static bool $isLazy = false;
}
