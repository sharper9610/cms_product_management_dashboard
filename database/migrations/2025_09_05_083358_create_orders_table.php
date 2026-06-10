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
            $table->string('order_id_2game')->unique();
            $table->string('consumer_ip', 45)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('payment_fee', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('grand_total', 10, 2)->default(0.00);
            $table->unsignedInteger('total_qty_ordered')->default(0);
            $table->decimal('total_amount_paid', 10, 2)->default(0.00);
            $table->decimal('total_amount_ordered', 10, 2)->default(0.00);
            $table->decimal('total_discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->enum('status', ['PENDING','PROCESSING', 'PARTIALLY_COMPLETED', 'COMPLETED', 'FAILED'])->default('PENDING');
            $table->timestamps();
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
