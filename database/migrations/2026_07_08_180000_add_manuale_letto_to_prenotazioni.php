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
            $table->timestamp('manuale_letto_confermato_at')->nullable()->after('categoria_patente_privato');
            $table->foreignId('manuale_letto_torre_id')
                ->nullable()
                ->after('manuale_letto_confermato_at')
                ->constrained('torri')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('manuale_letto_torre_id');
            $table->dropColumn('manuale_letto_confermato_at');
        });
    }
};
