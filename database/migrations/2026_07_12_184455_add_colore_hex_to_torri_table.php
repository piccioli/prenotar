<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('torri', function (Blueprint $table): void {
            $table->string('colore_hex', 7)->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('torri', function (Blueprint $table): void {
            $table->dropColumn('colore_hex');
        });
    }
};
