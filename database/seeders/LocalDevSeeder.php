<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Events\UserSetPasswordRequested;
use App\Models\Sezione;
use App\Models\User;
use App\Services\Import\ExcelImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder per ambienti local/testing.
 * Importa le 152 sezioni + 77 sottosezioni CAI Lombardia dall'Excel di progettazione,
 * imposta password='password' su tutti gli account e crea admin + GR dev.
 *
 * Uso: sail artisan migrate:fresh --seed
 *      oppure: sail artisan db:seed --class=LocalDevSeeder
 */
class LocalDevSeeder extends Seeder
{
    private const EXCEL_PATH = 'DOCUMENTI PER LA PROGETTAZIONE/2026_MS_Sezioni_SottoSezioni_GR_Gruppi Regionali ETS.xlsx';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->importaSeSeVuoto();
        $this->impostaPassword();
        $this->creaAdmin();
        $this->creaGr();
    }

    private function importaSeSeVuoto(): void
    {
        if (Sezione::count() > 0) {
            $this->command->info('Sezioni già presenti nel DB — import saltato.');

            return;
        }

        $path = base_path(self::EXCEL_PATH);

        if (! file_exists($path)) {
            $this->command->warn('File Excel non trovato: '.self::EXCEL_PATH);
            $this->command->warn('Esegui prima l\'import manuale dal pannello /admin.');

            return;
        }

        $this->command->info('Importazione sezioni/sottosezioni CAI Lombardia...');

        // Sopprime le email di SetPassword durante il seeding
        Event::fake([UserSetPasswordRequested::class]);

        $result = app(ExcelImportService::class)->import($path, importedById: null, force: true);

        $this->command->info(
            "Import completato: {$result->righeImportate} importate, ".
            "{$result->userCreati} utenti creati, {$result->righeInErrore} errori."
        );
    }

    private function impostaPassword(): void
    {
        $hashed = Hash::make('password');

        $count = User::whereHas(
            'roles',
            fn ($q) => $q->whereIn('name', ['sezione', 'sottosezione'])
        )->update([
            'password' => $hashed,
            'email_is_fallback' => false,
            'is_active' => true,
        ]);

        $this->command->info("Password 'password' impostata su {$count} utenti sezione/sottosezione.");
    }

    private function creaAdmin(): void
    {
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

        $this->command->info('Admin: admin@local.test / password');
    }

    private function creaGr(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'gr@local.test'],
            [
                'name' => 'GR Lombardia Dev',
                'password' => Hash::make('password'),
                'email_is_fallback' => false,
                'is_active' => true,
            ]
        );
        $user->syncRoles(['gr_manager']);

        $this->command->info('GR:    gr@local.test / password');
    }
}
