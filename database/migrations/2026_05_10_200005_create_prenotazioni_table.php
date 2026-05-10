<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenotazioni', function (Blueprint $table): void {
            $table->id();

            // chi prenota
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('sezione_id')
                ->nullable()
                ->constrained('sezioni')
                ->nullOnDelete();
            $table->foreignId('sottosezione_id')
                ->nullable()
                ->constrained('sottosezioni')
                ->nullOnDelete();

            // cosa (torre opzionale — il GR può riassegnare in approvazione)
            $table->foreignId('torre_id')
                ->nullable()
                ->constrained('torri')
                ->nullOnDelete();

            // evento
            $table->string('nome_evento');
            $table->string('tipo_evento');
            $table->text('descrizione_evento')->nullable();
            $table->string('indirizzo_evento');
            $table->date('data_inizio_evento');
            $table->date('data_fine_evento');

            // prenotazione torre
            $table->date('data_inizio_prenotazione')->index();
            $table->date('data_fine_prenotazione');

            // trasporto / logistica
            $table->date('data_ritiro')->nullable();
            $table->string('luogo_ritiro')->nullable();
            $table->date('data_riconsegna')->nullable();
            $table->string('luogo_riconsegna')->nullable();
            $table->string('azienda_trasporto')->default('montagna servizi');
            $table->string('targa_autoveicolo')->nullable();

            // responsabile in loco
            $table->string('responsabile_nome');
            $table->string('responsabile_titolo_cai')->nullable();
            $table->string('responsabile_codice_cai')->nullable();
            $table->string('responsabile_telefono');
            $table->string('responsabile_email');
            $table->string('responsabile_tipo'); // enum gestito a livello app (ResponsabileTipo)

            // workflow
            $table->string('status')->default('bozza')->index();
            $table->foreignId('approvato_da')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approvato_at')->nullable();
            $table->text('motivo_rifiuto')->nullable();
            $table->timestamp('pdf_firmato_at')->nullable();
            $table->string('pdf_firmato_path')->nullable();
            $table->timestamp('inviato_assicurazione_at')->nullable();
            $table->timestamp('concluso_at')->nullable();

            // archivio / soft delete
            $table->timestamp('archived_at')->nullable()->index();
            $table->softDeletes();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenotazioni');
    }
};
