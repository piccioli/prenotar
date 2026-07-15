<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\AuditLogResource;
use App\Models\ExcelImport;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;

class StatoSistemaWidget extends Widget
{
    private const int GIORNI_ERRORI_RECENTI = 7;

    private const int MAX_EVENTI = 5;

    protected static string $view = 'filament.admin.widgets.stato-sistema';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function getUtentiAttivi(): int
    {
        return User::active()->count();
    }

    public function getUtentiTotali(): int
    {
        return User::count();
    }

    public function getUtentiDisattivati(): int
    {
        return $this->getUtentiTotali() - $this->getUtentiAttivi();
    }

    public function getUltimoImport(): ?ExcelImport
    {
        return ExcelImport::query()->latest('created_at')->first();
    }

    public function getErroriRecenti(): int
    {
        return (int) DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDays(self::GIORNI_ERRORI_RECENTI))
            ->count();
    }

    public function getJobInAttesa(): int
    {
        return Queue::size();
    }

    /** @return Collection<int, Activity> */
    public function getUltimiEventi(): Collection
    {
        return Activity::query()
            ->with('causer')
            ->latest('created_at')
            ->limit(self::MAX_EVENTI)
            ->get();
    }

    public function getUrlAuditLog(): string
    {
        return AuditLogResource::getUrl();
    }

    public function getUrlHorizon(): string
    {
        return '/horizon';
    }
}
