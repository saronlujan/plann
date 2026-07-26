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
            $table->foreignId('account_id')->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type')->nullable();
            $table->string('type');
            $table->string('installment_frequency')->nullable();
            $table->unsignedSmallInteger('installments_total')->nullable();
            $table->unsignedSmallInteger('installment_number')->nullable();
            $table->decimal('interest_amount', 10, 2)->nullable();
            $table->string('attachment_path')->nullable();
            $table->uuid('series_uuid')->nullable()->index();
            $table->date('effective_date')->index();
            $table->date('paid_at')->nullable()->index();
            $table->date('effective_until')->nullable()->index();
            $table->date('adjustment_month')->nullable()->index();
            $table->decimal('amount', 18, 2);
            $table->decimal('adjustment_amount', 18, 2)->default(0);
            $table->string('description');
            $table->timestamps();

            $table->index(['tenant_id', 'series_uuid']);
            $table->index(['tenant_id', 'effective_date']);
            $table->index(['tenant_id', 'adjustment_month']);
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
