<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Torre;
use Illuminate\Database\Seeder;

class TorriSeeder extends Seeder
{
    public function run(): void
    {
        Torre::updateOrCreate(
            ['nome' => 'Torre di arrampicata 1'],
            [
                'indirizzo_deposito' => 'Deposito Assago/Seprio — da confermare con Montagna Servizi',
                'is_active' => true,
            ],
        );

        Torre::updateOrCreate(
            ['nome' => 'Torre di arrampicata 2'],
            [
                'indirizzo_deposito' => 'Deposito Nembro — da confermare con Montagna Servizi',
                'is_active' => true,
            ],
        );
    }
}
