<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GeneraCollaudoPdfCommand extends Command
{
    protected $signature = 'collaudo:pdf {version : Numero di versione, es. 0.11.0}';

    protected $description = 'Genera la versione PDF (carta intestata Montagna Servizi) del documento di collaudo di una release';

    public function handle(): int
    {
        $version = (string) $this->argument('version');
        $dir = base_path("docs/collaudo/{$version}");
        $markdownPath = "{$dir}/collaudo-v{$version}.md";
        $logoPath = base_path('docs/collaudo/_assets/montagna-servizi-logo.png');

        if (! file_exists($markdownPath)) {
            $this->error("Documento non trovato: {$markdownPath}");

            return self::FAILURE;
        }

        if (! file_exists($logoPath)) {
            $this->error("Logo Montagna Servizi non trovato: {$logoPath}");

            return self::FAILURE;
        }

        // Il font DejaVu Sans usato da Dompdf non ha i glifi delle emoji: senza questa
        // sostituzione compaiono come quadratini vuoti nel PDF (restano invece nel .md,
        // dove GitHub le renderizza correttamente).
        $markdown = str_replace('📧 ', '', file_get_contents($markdownPath));
        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
        ]);

        $pdf = Pdf::loadView('pdf.collaudo-letterhead', [
            'content' => $html,
            'logoPath' => $logoPath,
        ])->setPaper('a4', 'portrait');

        $outputPath = "{$dir}/collaudo-v{$version}.pdf";
        file_put_contents($outputPath, $pdf->output());

        $this->info("PDF generato: {$outputPath}");

        return self::SUCCESS;
    }
}
