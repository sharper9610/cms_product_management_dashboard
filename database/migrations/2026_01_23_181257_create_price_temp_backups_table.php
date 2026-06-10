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
        Schema::create('price_temp_backups', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('source')->default(1);
            $table->integer('product_id')->unsigned()->index();
            $table->char('country_code', 2);
            $table->decimal('steam_price', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_temp_backups');
    }
};
