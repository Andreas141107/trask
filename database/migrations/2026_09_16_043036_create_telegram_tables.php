<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('bot_username')->unique();
            $table->string('bot_token');
            $table->string('webhook_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('team_telegrams', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_bot_id')->constrained()->cascadeOnDelete();
            $table->primary(['team_id', 'telegram_bot_id']);
            $table->timestamps();
        });

        Schema::create('telegram_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_bot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_id')->nullable();
            $table->string('type'); // incoming, outgoing
            $table->string('sender_id');
            $table->string('recipient_id');
            $table->text('message');
            $table->string('status'); // sent, delivered, read, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_logs');
        Schema::dropIfExists('team_telegrams');
        Schema::dropIfExists('telegram_bots');
    }
};
