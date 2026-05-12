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
            $table->dropColumn('pdf_firmato_path');
        });
    }

    public function down(): void
    {
        Schema::table('prenotazioni', function (Blueprint $table): void {
            $table->string('pdf_firmato_path')->nullable()->after('pdf_firmato_at');
        });
    }
};
