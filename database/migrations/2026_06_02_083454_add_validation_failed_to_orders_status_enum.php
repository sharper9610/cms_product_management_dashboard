<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders_status_enum', function (Blueprint $table) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'PENDING',
            'PROCESSING',
            'COMPLETED',
            'PARTIALLY_COMPLETED',
            'FAILED',
            'CANCELLED',
            'VALIDATION_FAILED'
        ) NOT NULL DEFAULT 'PENDING'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders_status_enum', function (Blueprint $table) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'PENDING',
            'PROCESSING',
            'COMPLETED',
            'PARTIALLY_COMPLETED',
            'FAILED',
            'CANCELLED'
        ) NOT NULL DEFAULT 'PENDING'");
        });
    }
};
