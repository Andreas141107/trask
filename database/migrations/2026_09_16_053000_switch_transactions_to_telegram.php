<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE transactions MODIFY COLUMN source ENUM("web", "whatsapp", "telegram") NOT NULL DEFAULT "web"');

        if (! Schema::hasColumn('transactions', 'telegram_sender')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('telegram_sender')->nullable();
            });
        }

        if (Schema::hasColumn('transactions', 'whatsapp_sender')) {
            DB::statement('UPDATE transactions SET telegram_sender = whatsapp_sender, source = "telegram" WHERE source = "whatsapp"');

            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('whatsapp_sender');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('transactions', 'whatsapp_sender')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('whatsapp_sender')->nullable();
            });
        }

        if (Schema::hasColumn('transactions', 'telegram_sender')) {
            DB::statement('UPDATE transactions SET whatsapp_sender = telegram_sender, source = "whatsapp" WHERE source = "telegram"');

            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('telegram_sender');
            });
        }

        DB::statement('ALTER TABLE transactions MODIFY COLUMN source ENUM("web", "whatsapp") NOT NULL DEFAULT "web"');
    }
};