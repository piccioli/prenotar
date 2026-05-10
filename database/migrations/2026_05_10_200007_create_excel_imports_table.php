<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excel_imports', function (Blueprint $table): void {
            $table->id();
            $table->string('filename');
            $table->string('hash')->index();
            $table->foreignId('imported_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->unsignedInteger('righe_importate')->default(0);
            $table->unsignedInteger('righe_aggiornate')->default(0);
            $table->unsignedInteger('righe_in_errore')->default(0);
            $table->json('log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excel_imports');
    }
};
