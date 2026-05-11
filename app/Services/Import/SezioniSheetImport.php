<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Events\UserSetPasswordRequested;
use App\Models\Sezione;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

final class SezioniSheetImport implements ToCollection, WithHeadingRow
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
            if ($codice === '') {
                $this->inErrore++;
                $this->log[] = 'Riga sezione senza codice, saltata.';

                continue;
            }

            try {
                $sezione = Sezione::updateOrCreate(
                    ['codice' => $codice],
                    [
                        'nominativo' => trim((string) ($row['nominativo'] ?? '')),
                        'regione' => 'LOMBARDIA',
                        'provincia' => trim((string) ($row['provincia'] ?? '')) ?: null,
                        'anno_fondazione' => $this->toInt($row['anno_di_fondazione'] ?? null),
                        'iscritti_count' => $this->toInt($row['iscritti'] ?? null),
                        'sito_web' => trim((string) ($row['sitoweb'] ?? '')) ?: null,
                        'telefono' => trim((string) ($row['telefono'] ?? '')) ?: null,
                        'pec' => trim((string) ($row['pec'] ?? '')) ?: null,
                        'indirizzo' => trim((string) ($row['indirizzo_sede_legale'] ?? '')) ?: null,
                        'presidente_nome' => trim((string) ($row['presidente'] ?? '')) ?: null,
                        'ente_terzo_settore' => strtoupper(trim((string) ($row['ente_del_terzo_settore'] ?? ''))) === 'ETS',
                        'is_active' => true,
                    ]
                );

                if ($sezione->wasRecentlyCreated) {
                    $this->importate++;
                } else {
                    $this->aggiornate++;
                }

                $this->codiciProcessati[] = $codice;
                $this->syncUser($sezione, (string) ($row['email'] ?? ''));
            } catch (\Throwable $e) {
                $this->inErrore++;
                $this->log[] = "Sezione {$codice}: {$e->getMessage()}";
            }
        }
    }

    private function syncUser(Sezione $sezione, string $emailRaw): void
    {
        $email = strtolower(trim($emailRaw));
        $isReal = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $email = $isReal ? $email : "{$sezione->codice}@grlomct.it";

        $user = User::firstOrCreate(
            ['codice_cai' => $sezione->codice],
            [
                'name' => $sezione->nominativo,
                'email' => $email,
                'email_is_fallback' => ! $isReal,
                'sezione_id' => $sezione->id,
                'sottosezione_id' => null,
                'is_active' => true,
                'password' => Hash::make(Str::random(40)),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $user->assignRole('sezione');
            $this->userCreati++;
            event(new UserSetPasswordRequested($user));
        } else {
            $user->update([
                'name' => $sezione->nominativo,
                'email' => $email,
                'email_is_fallback' => ! $isReal,
                'is_active' => true,
            ]);
        }
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
