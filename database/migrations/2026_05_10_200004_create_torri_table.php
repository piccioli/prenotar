<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('torri', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->longText('descrizione')->nullable();
            $table->string('indirizzo_deposito');
            $table->string('foto_path')->nullable();
            $table->string('specs_tecniche_pdf_path')->nullable();
            $table->string('manuale_pdf_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('torri');
    }
};
