<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuovo utente manuale'),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        $totale = User::query()->count();
        $sezioni = User::query()->whereNotNull('sezione_id')->whereNull('sottosezione_id')->count();
        $sottosezioni = User::query()->whereNotNull('sottosezione_id')->count();
        $gr = User::role('gr_manager')->count();
        $admin = User::role('admin')->count();

        return "{$totale} account: {$sezioni} Sezioni, {$sottosezioni} Sottosezioni, {$gr} GR, {$admin} admin.";
    }
}
