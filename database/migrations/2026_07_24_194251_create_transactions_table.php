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
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // Plain columns: the constraint is composite (see below), because a
            // single-column FK would happily accept another tenant's account.
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('category_id')->nullable();
            // Who the money came from or went to: the client on an income, the
            // provider on an expense.
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('movement_type')->nullable();
            $table->boolean('is_transfer')->default(false);
            $table->string('type');
            $table->string('installment_frequency')->nullable();
            $table->unsignedSmallInteger('installments_total')->nullable();
            $table->unsignedSmallInteger('installment_number')->nullable();
            $table->decimal('interest_amount', 10, 2)->nullable();
            $table->string('attachment')->nullable();
            $table->uuid('series_uuid')->nullable()->index();
            // An adjustment row that stands for "this occurrence was removed":
            // the projector expands a series from its master, so the only way to
            // take one month out is to record that it is gone.
            $table->boolean('is_skipped')->default(false);
            $table->date('effective_date')->index();
            $table->date('paid_at')->nullable()->index();
            $table->date('effective_until')->nullable()->index();
            $table->date('adjustment_month')->nullable()->index();
            $table->decimal('amount', 18, 2);
            $table->decimal('adjustment_amount', 18, 2)->default(0);
            $table->string('description');
            // A short label of the user's own, and the long-form version of it.
            $table->string('note')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            // Target of tag_transaction's composite foreign key.
            $table->unique(['tenant_id', 'id']);

            // The tenant travels with the reference, so a row can only ever point
            // at a parent inside its own workspace. Deletes are left as NO ACTION
            // so a tenant-wide cascade can still unwind in a single statement;
            // the models null these out explicitly on delete.
            $table->foreign(['tenant_id', 'account_id'])->references(['tenant_id', 'id'])->on('accounts');
            $table->foreign(['tenant_id', 'category_id'])->references(['tenant_id', 'id'])->on('categories');
            $table->foreign(['tenant_id', 'contact_id'])->references(['tenant_id', 'id'])->on('contacts');

            $table->index(['tenant_id', 'series_uuid']);
            $table->index(['tenant_id', 'effective_date']);
            $table->index(['tenant_id', 'adjustment_month']);
            $table->index(['tenant_id', 'contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
