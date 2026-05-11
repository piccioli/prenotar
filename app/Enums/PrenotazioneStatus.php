<?php

declare(strict_types=1);

namespace App\Enums;

enum PrenotazioneStatus: string
{
    case Bozza = 'bozza';
    case Inviata = 'inviata';
    case Approvata = 'approvata';
    case Annullata = 'annullata';
    case InviatoPdfFirmato = 'inviato_pdf_firmato';
    case InviatoAssicurazione = 'inviato_assicurazione';
    case Concluso = 'concluso';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Concluso, self::Annullata => true,
            default => false,
        };
    }

    /** True per gli stati che bloccano un nuovo invio dalla stessa sezione (§5.1.5). */
    public function isActive(): bool
    {
        return match ($this) {
            self::Inviata, self::Approvata, self::InviatoPdfFirmato => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Bozza => 'Bozza',
            self::Inviata => 'Inviata',
            self::Approvata => 'Approvata',
            self::Annullata => 'Annullata',
            self::InviatoPdfFirmato => 'PDF firmato inviato',
            self::InviatoAssicurazione => 'Inviata all\'assicurazione',
            self::Concluso => 'Conclusa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bozza => 'gray',
            self::Inviata => 'warning',
            self::Approvata => 'success',
            self::Annullata => 'danger',
            self::InviatoPdfFirmato => 'info',
            self::InviatoAssicurazione => 'primary',
            self::Concluso => 'gray',
        };
    }
}
