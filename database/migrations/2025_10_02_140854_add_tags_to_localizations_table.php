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
            $table->text('seo_tags')->nullable()->after('long_description');
            $table->text('genre_tags')->nullable()->after('seo_tags');
            $table->text('franchise_tags')->nullable()->after('genre_tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('localizations', function (Blueprint $table) {
            $table->dropColumn(['seo_tags', 'genre_tags', 'franchise_tags']);
        });
    }
};
