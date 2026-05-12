<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazionePdfFirmatoCaricato;
use App\Listeners\SendPrenotazionePdfFirmatoCaricatoNotification;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Models\User;
use App\Notifications\PrenotazionePdfFirmatoCaricatoNotification;
use App\Services\PrenotazioneStateMachine;
use App\Settings\GrSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakePdfFile(string $name = 'firmato.pdf'): UploadedFile
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
    $settings->save();
});

test('caricaPdfFirmato su prenotazione Approvata aggiorna status e history', function (): void {
    Storage::fake('local');

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    $file = fakePdfFile();

    $this->machine->caricaPdfFirmato($pren, $this->sezione, $file);

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::InviatoPdfFirmato)
        ->and($pren->pdf_firmato_at)->not->toBeNull()
        ->and($pren->getFirstMedia('pdf_firmato'))->not->toBeNull();

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->user_id)->toBe($this->sezione->id)
        ->and($history->status_from)->toBe(PrenotazioneStatus::Approvata)
        ->and($history->status_to)->toBe(PrenotazioneStatus::InviatoPdfFirmato)
        ->and($history->note)->toContain('PDF firmato');
});

test('caricaPdfFirmato dispatcha evento PrenotazionePdfFirmatoCaricato', function (): void {
    Storage::fake('local');
    Event::fake([PrenotazionePdfFirmatoCaricato::class]);

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    $this->machine->caricaPdfFirmato($pren, $this->sezione, fakePdfFile());

    Event::assertDispatched(PrenotazionePdfFirmatoCaricato::class, fn ($e) => $e->prenotazione->id === $pren->id);
});

test('caricaPdfFirmato lancia DomainException se status non è Approvata', function (): void {
    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione->id]);
    $file = fakePdfFile();

    expect(fn () => $this->machine->caricaPdfFirmato($pren, $this->sezione, $file))
        ->toThrow(DomainException::class, 'APPROVATA');
});

test('caricaPdfFirmato lancia DomainException se user non è il proprietario', function (): void {
    Storage::fake('local');

    $altroUser = User::factory()->sezione()->create();
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);
    $file = fakePdfFile();

    expect(fn () => $this->machine->caricaPdfFirmato($pren, $altroUser, $file))
        ->toThrow(DomainException::class, 'proprietaria');
});

test('listener invia notifica al GR quando PDF firmato caricato', function (): void {
    Notification::fake();
    Storage::fake('local');

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    $listener = app(SendPrenotazionePdfFirmatoCaricatoNotification::class);
    $listener->handle(new PrenotazionePdfFirmatoCaricato($pren->fresh()));

    Notification::assertSentOnDemand(
        PrenotazionePdfFirmatoCaricatoNotification::class,
        fn ($notification, $channels, $notifiable) => in_array('gr@example.com', (array) $notifiable->routes['mail'], strict: true)
    );
});
