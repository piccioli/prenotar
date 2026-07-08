<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table): void {
            $table->string('tipo_mezzo')->default('aziendale')->after('azienda_trasporto');
            $table->string('categoria_patente_privato')->nullable()->after('tipo_mezzo');
        });
    }

    public function down(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table): void {
            $table->dropColumn(['tipo_mezzo', 'categoria_patente_privato']);
        });
    }
};
