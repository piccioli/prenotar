<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sezioni', function (Blueprint $table): void {
            $table->id();
            $table->string('codice')->unique()->index();
            $table->string('nominativo');
            $table->string('regione')->index();
            $table->string('provincia')->nullable();
            $table->string('email')->nullable();
            $table->string('pec')->nullable();
            $table->string('sito_web')->nullable();
            $table->string('telefono')->nullable();
            $table->string('indirizzo')->nullable();
            $table->unsignedInteger('iscritti_count')->nullable();
            $table->string('presidente_nome')->nullable();
            $table->unsignedSmallInteger('anno_fondazione')->nullable();
            $table->boolean('ente_terzo_settore')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sezioni');
    }
};
