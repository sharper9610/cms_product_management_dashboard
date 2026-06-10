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
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->boolean('source');
            $table->char('currency', 3);
            $table->text('country_code')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('steam_price', 12, 2)->nullable();
            $table->decimal('cost_estimate', 12, 2)->nullable();
            $table->unsignedInteger('discount_valid_from')->nullable();
            $table->unsignedInteger('discount_valid_to')->nullable();
            $table->decimal('discount_percent', 6, 3)->nullable();
            $table->unsignedInteger('discount_valid_from_2game')->nullable();
            $table->unsignedInteger('discount_valid_to_2game')->nullable();
            $table->decimal('discount_percent_2game', 6, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('price_update_timestamp');
            $table->decimal('min_value', 12, 2)->nullable();
            $table->decimal('max_value', 12, 2)->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('sku')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
