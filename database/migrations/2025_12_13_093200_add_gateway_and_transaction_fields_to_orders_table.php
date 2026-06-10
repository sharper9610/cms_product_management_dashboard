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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('gateway_fee', 12, 6)->default(0)->after('payment_fee');
            $table->decimal('gateway_fee_eur', 12, 6)->default(0)->after('gateway_fee');
            $table->decimal('shopify_plus_fee', 12, 6)->default(0)->after('gateway_fee_eur');
            $table->decimal('shopify_plus_fee_eur', 12, 6)->default(0)->after('shopify_plus_fee');
            $table->string('sale_transaction_id', 255)->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_fee',
                'gateway_fee_eur',
                'shopify_plus_fee',
                'shopify_plus_fee_eur',
                'sale_transaction_id',
            ]);
        });
    }
};
