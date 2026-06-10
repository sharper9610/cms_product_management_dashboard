<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add bonus cap column
            $table->integer('bonus_cap_percent')
                ->nullable()
                ->default(0)
                ->after('status')
                ->comment('0-100: Percentage of price that can be paid with bonus');
            
            // Add index for bonus cap queries
            $table->index('bonus_cap_percent');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['bonus_cap_percent']);
            $table->dropColumn('bonus_cap_percent');
        });
    }
};