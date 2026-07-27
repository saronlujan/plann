<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // ISO 3166-1 alpha-2: BR, PY, AR.
            $table->string('code', 2)->unique();
            // The currency a workspace in this country starts with.
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // tenants is created long before this table, so the column is declared
        // there and constrained here.
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['country_id']);
        });

        Schema::dropIfExists('countries');
    }
};
