<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            create or replace function app_current_tenant_id()
            returns bigint
            language sql
            stable
            as $$
                select nullif(current_setting('app.tenant_id', true), '')::bigint
            $$;
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('drop function if exists app_current_tenant_id();');
    }
};
