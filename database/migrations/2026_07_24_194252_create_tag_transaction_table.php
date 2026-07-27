<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_transaction', function (Blueprint $table): void {
            // tenant_id is carried on the pivot purely so both sides can be
            // constrained to the same workspace: a single-column foreign key
            // would accept a tag belonging to somebody else.
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('tag_id');

            $table->primary(['transaction_id', 'tag_id']);

            $table->foreign(['tenant_id', 'transaction_id'])
                ->references(['tenant_id', 'id'])
                ->on('transactions')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'tag_id'])
                ->references(['tenant_id', 'id'])
                ->on('tags')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_transaction');
    }
};
