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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_variant_id')->constrained()->onDelete('cascade');
            $table->enum('unit', ['kg', 'pcs', 'meter']);
            $table->decimal('qty', 10, 2);
            $table->decimal('price_per_unit_snapshot', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->text('note')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
