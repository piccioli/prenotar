<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Prenotazione;
use App\Services\PdfGenerator;
use App\Settings\GrSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;

class Modulo3Mail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Prenotazione $prenotazione,
        public readonly GrSettings $settings,
    ) {}

    public function envelope(): Envelope
    {
        $to = array_filter(
            $this->settings->emails_assicurazione,
            fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        );

        $replyTo = array_filter(
            $this->settings->emails_notifiche_gr,
            fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        );

        $nomeTorre = $this->prenotazione->torre->nome ?? 'torre';

        return new Envelope(
            to: array_values($to),
            cc: [$this->prenotazione->user->email],
            replyTo: array_values($replyTo),
            subject: "Modulo 3 — attivazione polizze trasporto {$nomeTorre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.modulo3',
        );
    }

    /** @return Attachment[] */
    public function attachments(): array
    {
        $p = $this->prenotazione;
        $settings = $this->settings;
        $attachments = [];

        $attachments[] = Attachment::fromData(
            fn (): string => app(PdfGenerator::class)->modulo3($p, $settings)->output(),
            "Modulo3_{$p->id}.pdf",
        )->withMime('application/pdf');

        if ($settings->documento_presidente_path !== null
            && Storage::disk('local')->exists($settings->documento_presidente_path)) {
            $attachments[] = Attachment::fromStorageDisk('local', $settings->documento_presidente_path);
        }

        return $attachments;
    }
}
