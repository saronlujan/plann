<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            // Null means the shared catalogue everyone sees; a tenant id means a
            // currency that workspace added for itself.
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 4);
            $table->string('symbol', 8);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        // A composite unique treats NULLs as distinct, so it would happily accept
        // two global 'BRL' rows. This is what actually keeps the catalogue unique.
        // Declared after the table exists and *after* every structural change to
        // it: SQLite rebuilds a table to add a constraint, and the rebuild drops
        // the WHERE clause, silently turning this into a plain unique on code.
        DB::statement('create unique index currencies_global_code_unique on currencies (code) where tenant_id is null');

        // users.default_currency_id and tenants.currency_id are declared with
        // their own tables (created first); the constraints can only be added now.
        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('default_currency_id')->references('id')->on('currencies')->nullOnDelete();
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['currency_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['default_currency_id']);
        });

        Schema::dropIfExists('currencies');
    }
};
