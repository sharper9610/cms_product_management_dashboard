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
        Schema::create('pn_product_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index()->comment('Reference to products table');
            $table->text('countries')->nullable()->comment('Serialized or JSON array of countries this stock applies to');
            $table->unsignedInteger('qty')->default(0)->comment('Available stock quantity');
            $table->string('geolock', 64)->nullable()->comment('Geo restriction e.g. Worldwide, EU, US');
            $table->unsignedInteger('stock_update_timestamp')->nullable()->comment('Last stock sync timestamp');
            $table->timestampsTz();
            $table->foreign('product_id')->references('sku')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pn_product_stocks');
    }
};
