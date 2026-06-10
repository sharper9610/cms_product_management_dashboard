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
          $table->string('scrape_url', 255)->nullable()->after('price_source');
          $table->string('concept_id',255)->nullable()->after('scrape_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
          $table->dropColumn(['scrape_url', 'concept_id']);
        });
    }
};
