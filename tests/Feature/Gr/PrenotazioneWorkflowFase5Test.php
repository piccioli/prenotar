<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazionePdfFirmatoCaricato;
use App\Mail\Modulo3Mail;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Models\User;
use App\Services\PrenotazioneStateMachine;
use App\Settings\GrSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakePdfFileE2e(string $name = 'firmato.pdf'): UploadedFile
{
    $tmpPath = tempnam(sys_get_temp_dir(), 'test_pdf_');
    file_put_contents($tmpPath, base64_decode('JVBERi0xLjAKMSAwIG9iajw8L1R5cGUvQ2F0YWxvZy9QYWdlcyAyIDAgUj4+ZW5kb2JqIDIgMCBvYmo8PC9UeXBlL1BhZ2VzL0tpZHNbMyAwIFJdL0NvdW50IDE+PmVuZG9iaiAzIDAgb2JqPDwvVHlwZS9QYWdlL01lZGlhQm94WzAgMCAzIDNdPj5lbmRvYmogeHJlZiAwIDQgMDAwMDAwMDAwMCA2NTUzNSBmIDAwMDAwMDAwMDkgMDAwMDAgbiAwMDAwMDAwMDU4IDAwMDAwIG4gMDAwMDAwMDExNSAwMDAwMCBuIHRyYWlsZXI8PC9TaXplIDQvUm9vdCAxIDAgUj4+IHN0YXJ0eHJlZiAxNzQKJSVFT0YK'));

    return new UploadedFile($tmpPath, $name, 'application/pdf', null, true);
}

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->torre = Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
    $this->gr = User::factory()->grManager()->create();
    $this->sezione = User::factory()->sezione()->create();
    $this->machine = app(PrenotazioneStateMachine::class);

    $settings = app(GrSettings::class);
    $settings->emails_notifiche_gr = ['gr@example.com'];
    $settings->emails_assicurazione = ['assicurazione@example.com'];
    $settings->presidente_nome = 'Mario Rossi';
    $settings->giorni_minimi_caricamento_documenti = 10;
    $settings->save();
});

test('workflow E2E Fase 5: APPROVATA → INVIATO_PDF_FIRMATO → INVIATO_ASSICURAZIONE', function (): void {
    Storage::fake('local');
    Mail::fake();
    // Solo PrenotazionePdfFirmatoCaricato è faked (per bloccare notifica email GR nel test).
    // PrenotazioneInviataAssicurazione non è faked: il listener reale fa queue del Modulo3Mail.
    Event::fake([PrenotazionePdfFirmatoCaricato::class]);

    // Setup: prenotazione già approvata
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'nome_evento' => 'Evento Test Fase 5',
        'tipo_evento' => 'Corso',
        'indirizzo_evento' => 'Via Test 1, Como',
        'data_inizio_prenotazione' => today()->addDays(30)->toDateString(),
        'data_fine_prenotazione' => today()->addDays(35)->toDateString(),
        'data_inizio_evento' => today()->addDays(31)->toDateString(),
        'data_fine_evento' => today()->addDays(34)->toDateString(),
        'responsabile_nome' => 'Mario Bianchi',
        'responsabile_tipo' => 'istruttore',
        'responsabile_telefono' => '3331234567',
        'responsabile_email' => 'mario@test.it',
    ]);

    $historyCountPrima = PrenotazioneHistory::where('prenotazione_id', $pren->id)->count();

    // Step 1 — sezione carica PDF firmato
    $file = fakePdfFileE2e();
    $this->machine->caricaPdfFirmato($pren, $this->sezione, $file);

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::InviatoPdfFirmato)
        ->and($pren->pdf_firmato_at)->not->toBeNull()
        ->and($pren->getFirstMedia('pdf_firmato'))->not->toBeNull();

    expect(PrenotazioneHistory::where('prenotazione_id', $pren->id)->count())->toBe($historyCountPrima + 1);

    Event::assertDispatched(PrenotazionePdfFirmatoCaricato::class);

    // Step 2 — GR invia all'assicurazione (listener reale entra in azione, mail in queue)
    $this->machine->inviaAssicurazione($pren->fresh(), $this->gr);

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::InviatoAssicurazione)
        ->and($pren->inviato_assicurazione_at)->not->toBeNull();

    expect(PrenotazioneHistory::where('prenotazione_id', $pren->id)->count())->toBe($historyCountPrima + 2);

    Mail::assertQueued(Modulo3Mail::class, fn (Modulo3Mail $mail) => $mail->hasTo('assicurazione@example.com')
        && $mail->hasCc($this->sezione->email));
});
