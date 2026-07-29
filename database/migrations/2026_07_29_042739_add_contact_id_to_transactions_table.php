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
        // Contacts existed as a standalone address book; nothing financial ever
        // pointed at one. This is the unique the composite foreign key below
        // needs, so it has to land before that constraint is declared.
        Schema::table('contacts', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id']);
        });

        Schema::table('transactions', function (Blueprint $table): void {
            // Who the money came from or went to: the client on an income, the
            // provider on an expense. Plain column because the constraint is
            // composite — a single-column FK would accept another tenant's contact.
            $table->unsignedBigInteger('contact_id')->nullable()->after('category_id');

            $table->index(['tenant_id', 'contact_id']);
        });

        // Left as NO ACTION, like the account and category links, so a tenant-wide
        // delete still unwinds in one statement; the model clears it explicitly.
        // SQLite cannot add a constraint to an existing table, and the test suite
        // runs there — the request already scopes the contact to the tenant.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->foreign(['tenant_id', 'contact_id'])->references(['tenant_id', 'id'])->on('contacts');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropForeign(['tenant_id', 'contact_id']);
            });
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'contact_id']);
            $table->dropColumn('contact_id');
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'id']);
        });
    }
};
