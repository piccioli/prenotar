<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('codice_cai')->nullable()->unique()->after('email');
            $table->foreignId('sezione_id')
                ->nullable()
                ->after('codice_cai')
                ->constrained('sezioni')
                ->nullOnDelete();
            $table->foreignId('sottosezione_id')
                ->nullable()
                ->after('sezione_id')
                ->constrained('sottosezioni')
                ->nullOnDelete();
            $table->string('contact_email')->nullable()->after('sottosezione_id');
            $table->boolean('email_is_fallback')->default(false)->index()->after('contact_email');
            $table->boolean('is_active')->default(true)->index()->after('email_is_fallback');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['sezione_id']);
            $table->dropForeign(['sottosezione_id']);
            $table->dropIndex(['email_is_fallback']);
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'codice_cai',
                'sezione_id',
                'sottosezione_id',
                'contact_email',
                'email_is_fallback',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
