<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Same gap the earlier index migration closed, for a table added after it:
 * Postgres does not index a foreign key column on its own, so retiring a
 * currency was scanning every service.
 *
 * services.tenant_id needs nothing — it already leads the unique over
 * (tenant_id, name).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->index('currency_id', 'services_currency_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropIndex('services_currency_id_index');
        });
    }
};
