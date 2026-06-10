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
        Schema::create('products_skip_updates', function (Blueprint $table) {
          $table->id();
          $table->unsignedBigInteger('product_id');
          $table->string('field_name', 64);
          $table->boolean('skip_update')->default(false);
          $table->timestamps();
          $table->foreign('product_id')->references('sku')->on('products')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_skip_updates');
    }
};
