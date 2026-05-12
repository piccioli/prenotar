<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Torre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TorriSeeder extends Seeder
{
    private const ASSETS_DIR = __DIR__.'/LocalDevSeedersAssets';

    public function run(): void
    {
        $descrizione = <<<'DESC'
Torre di arrampicata mobile CityWall (CST) di proprietà del CAI GR Lombardia (valore €38.131,10).

Struttura a 3 facce di arrampicata su rimorchio. Massimo 3 arrampicatori contemporanei (uno per faccia).

Dimensioni da eretta: altezza totale 12,00 m — altezza parete 9,60 m — larghezza bracci 6,00 m — larghezza base parete 2,30 m.
Dimensioni rimorchio: lunghezza 8,20 m — larghezza 2,40 m — profondità laterale 3,20 m.

Sollevamento idraulico (batteria a bordo, ricarica 220 V). Può essere spostata manualmente da 2 persone.
Stabile fino a 90 km/h di vento. Abbassare obbligatoriamente quando il vento supera i 50 km/h.

Trasporto: patente categoria B+E, veicolo trainante idoneo a rimorchio da 2000 kg.
Non arrampicare oltre i 3 m senza corda. Verificare sempre le previsioni meteo locali prima e durante l'utilizzo.
DESC;

        $manualePath = $this->copiaManuale();
        $fotoPath = $this->copiaFoto();

        Torre::updateOrCreate(
            ['nome' => 'Torre di arrampicata 1'],
            [
                'descrizione' => trim($descrizione),
                'indirizzo_deposito' => 'Via Duccio di Boninsegna 21/23, 20145 Milano MI (Sede Legale CAI GR Lombardia — area deposito Assago/Seprio)',
                'foto_path' => $fotoPath,
                'manuale_pdf_path' => $manualePath,
                'specs_tecniche_pdf_path' => $manualePath,
                'is_active' => true,
            ],
        );

        Torre::updateOrCreate(
            ['nome' => 'Torre di arrampicata 2'],
            [
                'descrizione' => trim($descrizione),
                'indirizzo_deposito' => 'Via Pizzo della Presolana 15, 24125 Bergamo BG (Sede Operativa CAI GR Lombardia — area deposito Nembro)',
                'foto_path' => $fotoPath,
                'manuale_pdf_path' => $manualePath,
                'specs_tecniche_pdf_path' => $manualePath,
                'is_active' => true,
            ],
        );
    }

    private function copiaFoto(): string
    {
        $source = self::ASSETS_DIR.'/foto_torre.jpeg';

        if (! file_exists($source)) {
            $this->command->warn('TorriSeeder: foto_torre.jpeg non trovato in LocalDevSeedersAssets — foto non impostata.');

            return '';
        }

        $dest = 'torri/foto/foto_torre.jpeg';
        Storage::disk('local')->makeDirectory('torri/foto');
        Storage::disk('local')->put($dest, file_get_contents($source));

        return $dest;
    }

    private function copiaManuale(): string
    {
        $source = self::ASSETS_DIR.'/manuale_torre_arrampicata.pdf';

        if (! file_exists($source)) {
            $this->command->warn('TorriSeeder: manuale_torre_arrampicata.pdf non trovato in LocalDevSeedersAssets — path PDF non impostato.');

            return '';
        }

        $dest = 'torri/manuali/manuale_torre_arrampicata.pdf';
        Storage::disk('local')->makeDirectory('torri/manuali');
        Storage::disk('local')->put($dest, file_get_contents($source));

        return $dest;
    }
}
