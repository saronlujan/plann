<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 4)->unique();
            $table->string('symbol', 8);
            $table->timestamps();
        });

        // users.default_currency_id is declared with the users table (which is
        // created first); the constraint can only be added now.
        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('default_currency_id')->references('id')->on('currencies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['default_currency_id']);
        });

        Schema::dropIfExists('currencies');
    }
};
