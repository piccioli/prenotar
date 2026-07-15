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
            $table->dropColumn([
                'luogo_ritiro',
                'luogo_riconsegna',
                'tipo_mezzo',
                'azienda_trasporto',
                'categoria_patente_privato',
            ]);

            $table->string('nome_conducente')->nullable()->after('targa_autoveicolo');
            $table->timestamp('patente_be_dichiarata_at')->nullable()->after('nome_conducente');
        });
    }

    public function down(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table): void {
            $table->dropColumn(['nome_conducente', 'patente_be_dichiarata_at']);

            $table->string('luogo_ritiro')->nullable()->after('data_ritiro');
            $table->string('luogo_riconsegna')->nullable()->after('data_riconsegna');
            $table->string('azienda_trasporto')->default('montagna servizi')->after('targa_autoveicolo');
            $table->string('tipo_mezzo')->default('aziendale')->after('azienda_trasporto');
            $table->string('categoria_patente_privato')->nullable()->after('tipo_mezzo');
        });
    }
};
