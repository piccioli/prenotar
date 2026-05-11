<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Events\UserSetPasswordRequested;
use App\Models\Sezione;
use App\Models\Sottosezione;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

final class SottosezioniSheetImport implements ToCollection, WithHeadingRow
{
    public int $importate = 0;

    public int $aggiornate = 0;

    public int $inErrore = 0;

    public int $userCreati = 0;

    /** @var string[] */
    public array $log = [];

    /** @var string[] */
    public array $codiciProcessati = [];

    /** @param Collection<int, mixed> $rows */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (strtoupper(trim((string) ($row['regione'] ?? ''))) !== 'LOMBARDIA') {
                continue;
            }

            $codice = trim((string) ($row['codice'] ?? ''));
            $codiceSezione = trim((string) ($row['codice_sezione'] ?? ''));

            if ($codice === '') {
                $this->inErrore++;
                $this->log[] = 'Riga sottosezione senza codice, saltata.';

                continue;
            }

            if ($codiceSezione === '') {
                $this->inErrore++;
                $this->log[] = "Sottosezione {$codice}: codice_sezione mancante, saltata.";

                continue;
            }

            $sezione = Sezione::where('codice', $codiceSezione)->first();
            if ($sezione === null) {
                $this->inErrore++;
                $this->log[] = "Sottosezione {$codice}: sezione padre '{$codiceSezione}' non trovata, saltata.";

                continue;
            }

            try {
                $sottosezione = Sottosezione::updateOrCreate(
                    ['codice' => $codice],
                    [
                        'nominativo' => trim((string) ($row['nominativo'] ?? '')),
                        'sezione_id' => $sezione->id,
                        'codice_sezione' => $codiceSezione,
                        'regione' => 'LOMBARDIA',
                        'provincia' => trim((string) ($row['provincia'] ?? '')) ?: null,
                        'email' => trim((string) ($row['email'] ?? '')) ?: null,
                        'indirizzo' => trim((string) ($row['indirizzo'] ?? '')) ?: null,
                        'is_active' => true,
                    ]
                );

                if ($sottosezione->wasRecentlyCreated) {
                    $this->importate++;
                } else {
                    $this->aggiornate++;
                }

                $this->codiciProcessati[] = $codice;
                $this->syncUser($sottosezione, (string) ($row['email'] ?? ''), $sezione);
            } catch (\Throwable $e) {
                $this->inErrore++;
                $this->log[] = "Sottosezione {$codice}: {$e->getMessage()}";
            }
        }
    }

    private function syncUser(Sottosezione $sottosezione, string $emailRaw, Sezione $sezione): void
    {
        $email = strtolower(trim($emailRaw));
        $isReal = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $email = $isReal ? $email : "{$sottosezione->codice}@grlomct.it";

        $user = User::firstOrCreate(
            ['codice_cai' => $sottosezione->codice],
            [
                'name' => $sottosezione->nominativo,
                'email' => $email,
                'email_is_fallback' => ! $isReal,
                'sezione_id' => null,
                'sottosezione_id' => $sottosezione->id,
                'is_active' => true,
                'password' => Hash::make(Str::random(40)),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $user->assignRole('sezione');
            $this->userCreati++;
            event(new UserSetPasswordRequested($user->load('sottosezione.sezione')));
        } else {
            $user->update([
                'name' => $sottosezione->nominativo,
                'email' => $email,
                'email_is_fallback' => ! $isReal,
                'is_active' => true,
            ]);
        }
    }
}
