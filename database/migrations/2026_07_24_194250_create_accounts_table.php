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
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('kind')->default('account');
            $table->decimal('credit_limit', 18, 2)->nullable();
            $table->unsignedTinyInteger('closing_day')->nullable();
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->timestamps();

            // Target of the composite foreign keys that keep children in-tenant.
            $table->unique(['tenant_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
