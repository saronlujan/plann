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
        Schema::create('service_transaction', function (Blueprint $table): void {
            // A surrogate key rather than the pair, because service_id is nullable:
            // a line outlives the service it named.
            $table->id();
            // tenant_id is carried on the pivot purely so both sides can be
            // constrained to the same workspace: a single-column foreign key
            // would accept a service belonging to somebody else.
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('service_id')->nullable();
            // What this service was actually worth on this transaction. The sum of
            // the lines is the transaction's amount, so this is the only place the
            // split is recorded — reports never recompute it from the catalogue.
            $table->decimal('amount', 18, 2);
            $table->timestamps();

            // Deleting the transaction takes its lines: the money went with it.
            $table->foreign(['tenant_id', 'transaction_id'])
                ->references(['tenant_id', 'id'])
                ->on('transactions')
                ->cascadeOnDelete();

            // Retiring a service must not: the line keeps its amount and falls into
            // the unattributed bucket, so the total still reconciles. NO ACTION,
            // cleared by the model — a composite SET NULL would blank tenant_id too.
            $table->foreign(['tenant_id', 'service_id'])
                ->references(['tenant_id', 'id'])
                ->on('services');

            // One line per service on a transaction. NULLs compare as distinct, so
            // several orphaned lines can coexist, which is what we want.
            $table->unique(['transaction_id', 'service_id']);
            $table->index(['tenant_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_transaction');
    }
};
