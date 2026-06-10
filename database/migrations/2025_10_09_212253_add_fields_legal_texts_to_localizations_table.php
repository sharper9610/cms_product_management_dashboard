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
        Schema::table('localizations', function (Blueprint $table) {
            $table->text('legal_texts')->nullable()->after('long_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('localizations', function (Blueprint $table) {
            $table->dropColumn(['legal_texts']);
        });
    }
};
