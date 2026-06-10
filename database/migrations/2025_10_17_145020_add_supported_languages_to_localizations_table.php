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
            $table->text('supported_languages')->nullable()->after('long_description')
                ->comment('Stores supported languages for the product in this locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('localizations', function (Blueprint $table) {
            $table->dropColumn('supported_languages');
        });
    }
};
