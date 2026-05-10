<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sottosezioni', function (Blueprint $table): void {
            $table->id();
            $table->string('codice')->unique()->index();
            $table->string('nominativo');
            $table->foreignId('sezione_id')
                ->constrained('sezioni')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('codice_sezione')->index();
            $table->string('regione')->index();
            $table->string('provincia')->nullable();
            $table->string('email')->nullable();
            $table->string('indirizzo')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sottosezioni');
    }
};
