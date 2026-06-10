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
        Schema::create('order_item_failures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedTinyInteger('retry_attempt');
            $table->enum('previous_status', [
                'PENDING',
                'PROCESSING',
                'COMPLETED',
                'FAILED',
                'CANCELLED'
            ]);
            $table->integer('key_id')->nullable();
            $table->string('retailer_order_id')->nullable();
            $table->text('failed_reason')->nullable();
            $table->decimal('sales_price_including_vat', 10, 2);
            $table->decimal('sales_price_excluding_vat', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('giftcard_amount', 10, 2);
            $table->decimal('row_total', 10, 2);
            $table->string('currency_code', 3)->nullable();
            $table->unsignedTinyInteger('source');
            $table->timestamp('archived_at')->useCurrent();
            $table->timestamps();
            $table->index(['order_id', 'order_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_failures');
    }
};
