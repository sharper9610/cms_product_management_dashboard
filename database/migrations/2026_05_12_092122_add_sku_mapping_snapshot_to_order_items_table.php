<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('sku_mapping_snapshot')
                  ->nullable()
                  ->after('product_id')
                  ->comment('e.g. "10150 -> [1015, 1016, 1049]"; null if no mapping was applied');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('sku_mapping_snapshot');
        });
    }
};