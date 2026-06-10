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
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->unsignedBigInteger('product_id');
            $table->decimal('sales_price_including_vat', 10, 2)->default(0.00);
            $table->decimal('sales_price_excluding_vat', 10, 2)->default(0.00);
            $table->string('currency_code', 3)->nullable();
            $table->integer('key_id')->nullable();
            $table->string('retailer_order_id')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('vat_amount', 10, 2)->default(0.00);
            $table->decimal('giftcard_amount', 10, 2)->default(0.00);
            $table->decimal('row_total', 10, 2)->default(0.00);
            $table->enum('status', ['PENDING','PROCESSING', 'COMPLETED', 'FAILED'])->default('PENDING');
            $table->tinyInteger('source')->comment('1=Ztorm, 2=Incomm');
            $table->text('failed_reason')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
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
