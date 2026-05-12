<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PrenotazioneStatus;
use App\Events\UserSetPasswordRequested;
use App\Models\Prenotazione;
use App\Models\Sezione;
use App\Models\Torre;
use App\Models\User;
use App\Services\Import\ExcelImportService;
use App\Settings\GrSettings;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Seeder per ambienti local/testing.
 * Importa le 152 sezioni + 77 sottosezioni CAI Lombardia dall'Excel di progettazione,
 * imposta password='password' su tutti gli account e crea admin + GR dev.
 * Crea inoltre prenotazioni demo (prefisso nome "[DEV]") visibili sul calendario (stati >= Inviata).
 *
 * Uso: sail artisan migrate:fresh --seed
 *      oppure: sail artisan db:seed --class=LocalDevSeeder
 *
 * File Excel (in ordine di ricerca):
 *   1. `database/seeders/LocalDevSeedersAssets/<nome>.xlsx`
 *   2. `DOCUMENTI PER LA PROGETTAZIONE/<nome>.xlsx`
 *   3. `storage/app/private/<nome>.{xlsx,xls}` (singolo file)
 *
 * Dati presidente GR prelevati dalla carta d’identità reale in LocalDevSeedersAssets/:
 *   Emilio Aldeghi — nato a Lecco — 11/04/1958
 */
class LocalDevSeeder extends Seeder
{
    private const EXCEL_FILENAME = '2026_MS_Sezioni_SottoSezioni_GR_Gruppi Regionali ETS.xlsx';

    private const ASSETS_DIR = __DIR__.'/LocalDevSeedersAssets';

    private const DEMO_NOME_PREFIX = '[DEV] ';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'staging'])) {
            return;
        }

        activity()->disableLogging();

        try {
            $this->importaSeSeVuoto();
            $this->impostaPassword();
            $this->creaAdmin();
            $this->creaGr();
            $this->creaGrSettings();
            $this->creaPrenotazioniDemoCalendario();
        } finally {
            activity()->enableLogging();
        }
    }

    private function resolveExcelPath(): ?string
    {
        // 1. Cerca prima nella directory assets del seeder
        $assets = self::ASSETS_DIR.'/'.self::EXCEL_FILENAME;
        if (is_file($assets)) {
            return $assets;
        }

        // 2. Poi in DOCUMENTI PER LA PROGETTAZIONE/
        $documenti = base_path('DOCUMENTI PER LA PROGETTAZIONE/'.self::EXCEL_FILENAME);
        if (is_file($documenti)) {
            return $documenti;
        }

        // 3. Infine in storage/app/private/ (singolo file xlsx/xls)
        $baseName = pathinfo(self::EXCEL_FILENAME, PATHINFO_FILENAME);
        foreach (['xlsx', 'xls', 'XLSX', 'XLS'] as $ext) {
            $candidate = storage_path("app/private/{$baseName}.{$ext}");
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $privateDir = storage_path('app/private');
        if (! is_dir($privateDir)) {
            return null;
        }

        $matches = [];
        foreach (glob($privateDir.'/*.{xlsx,xls,XLSX,XLS}', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                $matches[] = $file;
            }
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function creaGrSettings(): void
    {
        $settings = app(GrSettings::class);

        $settings->presidente_nome = 'Emilio Aldeghi';
        $settings->presidente_nato_a = 'Lecco';
        $settings->presidente_data_nascita = '1958-04-11';
        $settings->emails_notifiche_gr = ['gr@local.test'];
        $settings->emails_assicurazione = ['assicurazione@local.test'];

        $destDir = 'gr/presidente';
        Storage::disk('local')->makeDirectory($destDir);

        $firma = self::ASSETS_DIR.'/DOC_Presidente_GR_Lombardia_FIRMA.jpg';
        if (is_file($firma)) {
            $destFirma = $destDir.'/DOC_Presidente_GR_Lombardia_FIRMA.jpg';
            Storage::disk('local')->put($destFirma, file_get_contents($firma));
            $settings->firma_presidente_path = $destFirma;
        }

        $ci = self::ASSETS_DIR.'/DOC_Presidente_GR_Lombardia_Carta_di_identita.pdf';
        if (is_file($ci)) {
            $destCi = $destDir.'/DOC_Presidente_GR_Lombardia_Carta_di_identita.pdf';
            Storage::disk('local')->put($destCi, file_get_contents($ci));
            $settings->documento_presidente_path = $destCi;
        }

        $settings->save();

        $this->command->info('GrSettings: presidente Emilio Aldeghi, firma e CI copiati in storage/app/private/gr/presidente/');
    }

    private function importaSeSeVuoto(): void
    {
        if (Sezione::count() > 0) {
            $this->command->info('Sezioni già presenti nel DB — import saltato.');

            return;
        }

        $path = $this->resolveExcelPath();

        if ($path === null) {
            $this->command->warn('File Excel non trovato (DOCUMENTI PER LA PROGETTAZIONE/'.self::EXCEL_FILENAME.' o storage/app/private/).');
            $this->command->warn('Esegui prima l\'import manuale dal pannello /admin.');

            return;
        }

        $this->command->info('Importazione sezioni/sottosezioni CAI Lombardia da: '.$path);

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

    private function creaPrenotazioniDemoCalendario(): void
    {
        if (! class_exists(Factory::class)) {
            $this->command->warn('Faker non installato (es. `composer install --no-dev`): prenotazioni demo calendario saltate.');

            return;
        }

        $torri = Torre::query()->where('is_active', true)->orderBy('id')->get();
        if ($torri->isEmpty()) {
            $this->command->warn('Nessuna torre attiva — prenotazioni demo calendario saltate.');

            return;
        }

        $users = User::query()
            ->where(fn ($q) => $q->whereNotNull('sezione_id')->orWhereNotNull('sottosezione_id'))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['sezione', 'sottosezione']))
            ->inRandomOrder()
            ->limit(14)
            ->get();

        if ($users->count() < 2) {
            $this->command->warn('Servono almeno 2 utenti sezione/sottosezione — prenotazioni demo calendario saltate (import Excel o crea utenti).');

            return;
        }

        $grId = User::query()->where('email', 'gr@local.test')->value('id');

        Prenotazione::withTrashed()
            ->where('nome_evento', 'like', self::DEMO_NOME_PREFIX.'%')
            ->forceDelete();

        $statiCalendario = [
            PrenotazioneStatus::Inviata,
            PrenotazioneStatus::Approvata,
            PrenotazioneStatus::InviatoPdfFirmato,
            PrenotazioneStatus::InviatoAssicurazione,
        ];

        $creati = 0;

        if ($torri->count() >= 2) {
            $torre1 = $torri->first();
            $torre2 = $torri->get(1);
            $utenteTorre1 = $users->shift();
            $utenteTorre2 = $users->shift();
            $inizio = Carbon::today()->addDays(21);
            $fine = (clone $inizio)->addDays(3);

            foreach (
                [
                    [$utenteTorre1, $torre1, 'Test sovrapposizione Torre 1'],
                    [$utenteTorre2, $torre2, 'Test sovrapposizione Torre 2'],
                ] as [$user, $torre, $titolo]
            ) {
                $dataInizioEvento = (clone $inizio)->subDay();
                $dataFineEvento = (clone $fine)->addDay();
                Prenotazione::factory()->inviata()->create([
                    'user_id' => $user->id,
                    'sezione_id' => $user->sezione_id,
                    'sottosezione_id' => $user->sottosezione_id,
                    'torre_id' => $torre->id,
                    'nome_evento' => self::DEMO_NOME_PREFIX.$titolo,
                    'data_inizio_prenotazione' => $inizio->toDateString(),
                    'data_fine_prenotazione' => $fine->toDateString(),
                    'data_inizio_evento' => $dataInizioEvento->toDateString(),
                    'data_fine_evento' => $dataFineEvento->toDateString(),
                    'data_ritiro' => (clone $inizio)->subDay()->toDateString(),
                    'data_riconsegna' => (clone $fine)->addDay()->toDateString(),
                    'approvato_da' => null,
                ]);
                $creati++;
            }
        } else {
            $this->command->warn('Serve almeno 2 torri attive per la demo di sovrapposizione T1/T2 — creo solo prenotazioni casuali.');
        }

        foreach ($users as $index => $user) {
            $torre = $torri[$index % $torri->count()];
            $dataInizioPren = Carbon::today()->addDays(fake()->numberBetween(-14, 120));
            $dataFinePren = (clone $dataInizioPren)->addDays(fake()->numberBetween(1, 5));
            $dataInizioEvento = (clone $dataInizioPren)->subDay();
            $dataFineEvento = (clone $dataFinePren)->addDay();

            $stato = $statiCalendario[$index % count($statiCalendario)];

            $extra = [
                'user_id' => $user->id,
                'sezione_id' => $user->sezione_id,
                'sottosezione_id' => $user->sottosezione_id,
                'torre_id' => $torre->id,
                'nome_evento' => self::DEMO_NOME_PREFIX.fake()->randomElement([
                    'Giornata arrampicata',
                    'Corso base',
                    'Raduno giovanile',
                    'Open day parete',
                    'Allenamento sezione',
                ]),
                'data_inizio_prenotazione' => $dataInizioPren->toDateString(),
                'data_fine_prenotazione' => $dataFinePren->toDateString(),
                'data_inizio_evento' => $dataInizioEvento->toDateString(),
                'data_fine_evento' => $dataFineEvento->toDateString(),
                'data_ritiro' => (clone $dataInizioPren)->subDay()->toDateString(),
                'data_riconsegna' => (clone $dataFinePren)->addDay()->toDateString(),
                'approvato_da' => in_array($stato, [
                    PrenotazioneStatus::Approvata,
                    PrenotazioneStatus::InviatoPdfFirmato,
                    PrenotazioneStatus::InviatoAssicurazione,
                ], true) ? $grId : null,
            ];

            if ($stato === PrenotazioneStatus::Inviata) {
                Prenotazione::factory()->inviata()->create($extra);
            } elseif ($stato === PrenotazioneStatus::Approvata) {
                Prenotazione::factory()->approvata()->create($extra);
            } elseif ($stato === PrenotazioneStatus::InviatoPdfFirmato) {
                Prenotazione::factory()->inviatoPdfFirmato()->create($extra);
            } else {
                Prenotazione::factory()->inviatoAssicurazione()->create($extra);
            }

            $creati++;
        }

        $this->command->info(sprintf(
            'Prenotazioni demo calendario: %d create (nome "%s…", stati visibili in calendario).',
            $creati,
            self::DEMO_NOME_PREFIX
        ));
    }
}
