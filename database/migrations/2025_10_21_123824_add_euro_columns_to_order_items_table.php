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
            $table->decimal('sales_price_including_vat_eur', 10, 2)->default(0.00)->after('sales_price_including_vat');
            $table->decimal('sales_price_excluding_vat_eur', 10, 2)->default(0.00)->after('sales_price_excluding_vat');
            $table->decimal('discount_amount_eur', 10, 2)->default(0.00)->after('discount_amount');
            $table->decimal('vat_amount_eur', 10, 2)->default(0.00)->after('vat_amount');
            $table->decimal('giftcard_amount_eur', 10, 2)->default(0.00)->after('giftcard_amount');
            $table->decimal('row_total_eur', 10, 2)->default(0.00)->after('row_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
             $table->dropColumn([
                'sales_price_including_vat_eur',
                'sales_price_excluding_vat_eur',
                'discount_amount_eur',
                'vat_amount_eur',
                'giftcard_amount_eur',
                'row_total_eur'
            ]);
        });
    }
};
