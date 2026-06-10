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
        Schema::table('prices', function (Blueprint $table) {
            $table->tinyInteger('is_promo_active')
                  ->default(0)
                  ->after('is_converted');
            $table->string('price_source')
                  ->nullable()
                  ->after('is_promo_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropColumn(['is_promo_active','price_source']);
        });
    }
};
