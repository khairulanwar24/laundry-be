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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('invoice_no');
            $table->enum('status', ['ANTRIAN', 'PROSES', 'SIAP_DIAMBIL', 'SELESAI', 'BATAL']);
            $table->enum('payment_status', ['UNPAID', 'PAID'])->default('UNPAID');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('perfume_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('discount_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount_value_snapshot', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->text('notes')->nullable();
            $table->datetime('checkin_at')->useCurrent();
            $table->datetime('eta_at')->nullable();
            $table->datetime('finished_at')->nullable();
            $table->datetime('canceled_at')->nullable();
            $table->datetime('collected_at')->nullable();
            $table->foreignId('collected_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Unique constraints
            $table->unique(['outlet_id', 'invoice_no']);

            // Indexes
            $table->index(['outlet_id', 'status']);
            $table->index(['outlet_id', 'created_at']);
            $table->index(['outlet_id', 'eta_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
