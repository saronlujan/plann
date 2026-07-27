<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Postgres does not create an index for a foreign key constraint (unlike MySQL),
 * so every `foreignId()->constrained()` column was being sequentially scanned.
 *
 * Tables whose tenant_id is already the leading column of a composite
 * unique/index (categories, tags, contacts, user_pins, transaction_notifications,
 * transactions) are already covered and are left alone, as is users.tenant_id,
 * which is unique because the app is single-user per workspace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            // The accounts page and the dashboard both filter by tenant+currency.
            $table->index(['tenant_id', 'currency_id'], 'accounts_tenant_id_currency_id_index');
            $table->index('currency_id', 'accounts_currency_id_index');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            // Statement and credit-card views load one account's history at a time.
            $table->index(['tenant_id', 'account_id'], 'transactions_tenant_id_account_id_index');
            // The dashboard builds one overview per active currency.
            $table->index(['tenant_id', 'currency_id', 'effective_date'], 'transactions_tenant_currency_date_index');
            $table->index('category_id', 'transactions_category_id_index');
        });

        Schema::table('tag_transaction', function (Blueprint $table): void {
            // The composite primary key already covers transaction_id, but not a
            // lookup that starts from the tag.
            $table->index('tag_id', 'tag_transaction_tag_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropIndex('accounts_tenant_id_currency_id_index');
            $table->dropIndex('accounts_currency_id_index');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('transactions_tenant_id_account_id_index');
            $table->dropIndex('transactions_tenant_currency_date_index');
            $table->dropIndex('transactions_category_id_index');
        });

        Schema::table('tag_transaction', function (Blueprint $table): void {
            $table->dropIndex('tag_transaction_tag_id_index');
        });
    }
};
