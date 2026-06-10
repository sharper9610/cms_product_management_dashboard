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
            $table->unsignedTinyInteger('retry_count')->default(0)->after('status');
            $table->timestamp('last_retry_at')->nullable()->after('retry_count');
            $table->string('last_failure_reason', 255)->nullable()->after('last_retry_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'retry_count',
                'last_retry_at',
                'last_failure_reason',
            ]);
        });
    }
};
