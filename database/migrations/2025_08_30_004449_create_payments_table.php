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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('method_id')->constrained('payment_methods')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->datetime('paid_at');
            $table->string('ref_no')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['SUCCESS', 'VOID'])->default('SUCCESS');
            $table->timestamps();

            // Indexes
            $table->index(['order_id']);
            $table->index(['method_id']);
            $table->index(['paid_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
