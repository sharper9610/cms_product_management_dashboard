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
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_upc', 255)->nullable()->after('seo_url_name');
            $table->decimal('merchant_commission_percentage', 10, 2)->nullable()->after('product_upc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_upc');
            $table->dropColumn('merchant_commission_percentage');
        });
    }
};
