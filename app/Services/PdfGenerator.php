<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prenotazione;
use App\Settings\GrSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

final class PdfGenerator
{
    public function richiestaParete(Prenotazione $p): DomPdf
    {
        return Pdf::loadView('pdf.richiesta_parete', ['p' => $p])
            ->setPaper('a4', 'portrait');
    }

    public function modulo3(Prenotazione $p, GrSettings $settings): DomPdf
    {
        return Pdf::loadView('pdf.modulo3', ['p' => $p, 'settings' => $settings])
            ->setPaper('a4', 'portrait');
    }
}
