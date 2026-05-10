<?php

declare(strict_types=1);

use App\Models\Prenotazione;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

function fakePdf(string $filename): UploadedFile
{
    return UploadedFile::fake()->createWithContent($filename, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n");
}

it('can attach a file to the delibera_consiglio collection', function (): void {
    $prenotazione = Prenotazione::factory()->create();

    $prenotazione->addMedia(fakePdf('delibera.pdf'))->toMediaCollection('delibera_consiglio');

    expect($prenotazione->getFirstMedia('delibera_consiglio'))->not->toBeNull();
});

it('delibera_consiglio is a single-file collection (replaces on re-add)', function (): void {
    $prenotazione = Prenotazione::factory()->create();

    $prenotazione->addMedia(fakePdf('delibera1.pdf'))->toMediaCollection('delibera_consiglio');
    $prenotazione->addMedia(fakePdf('delibera2.pdf'))->toMediaCollection('delibera_consiglio');

    expect($prenotazione->getMedia('delibera_consiglio'))->toHaveCount(1);
    expect($prenotazione->getFirstMedia('delibera_consiglio')?->file_name)->toBe('delibera2.pdf');
});
