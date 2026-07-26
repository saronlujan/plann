<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup log of due/upcoming reminders already sent, so the daily command never
 * notifies the same user about the same occurrence twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entry_key');
            $table->string('kind');
            $table->date('due_date');
            $table->timestamps();

            $table->unique(['user_id', 'entry_key', 'due_date', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_notifications');
    }
};
