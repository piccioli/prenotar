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
            $table->timestamp('reminder_t10_inviato_at')->nullable()->after('inviato_assicurazione_at');
            $table->timestamp('reminder_t2gg_inviato_at')->nullable()->after('reminder_t10_inviato_at');
        });
    }

    public function down(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table): void {
            $table->dropColumn(['reminder_t10_inviato_at', 'reminder_t2gg_inviato_at']);
        });
    }
};
