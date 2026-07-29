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
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // What the service is offered at. Only ever read to prefill a line the
            // moment it is appended: the line then keeps whatever was agreed, so
            // repricing a service never rewrites what past months earned.
            $table->decimal('default_price', 18, 2)->nullable();
            // Which currency that price is quoted in. A line is only prefilled when
            // it matches the transaction's currency — converting would need a rate
            // for the day, and none is recorded anywhere.
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('color')->default('zinc');
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            // Target of the composite foreign keys that keep children in-tenant.
            $table->unique(['tenant_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
