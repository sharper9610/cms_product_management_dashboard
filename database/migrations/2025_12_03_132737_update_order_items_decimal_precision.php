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
      Schema::table('order_items', function (Blueprint $table) {
        $table->decimal('sales_price_including_vat_eur', 12, 6)->change();
        $table->decimal('sales_price_excluding_vat_eur', 12, 6)->change();
        $table->decimal('discount_amount_eur', 12, 6)->change();
        $table->decimal('vat_amount_eur', 12, 6)->change();
        $table->decimal('giftcard_amount_eur', 12, 6)->change();
        $table->decimal('row_total_eur', 12, 6)->change();
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
          $table->decimal('sales_price_including_vat_eur', 10, 2)->change();
          $table->decimal('sales_price_excluding_vat_eur', 10, 2)->change();
          $table->decimal('discount_amount_eur', 10, 2)->change();
          $table->decimal('vat_amount_eur', 10, 2)->change();
          $table->decimal('giftcard_amount_eur', 10, 2)->change();
          $table->decimal('row_total_eur', 10, 2)->change();
        });
    }
};
