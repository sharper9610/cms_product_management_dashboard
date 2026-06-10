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
          $table->string('platforms')->nullable()->after('concept_id');
          $table->text('description')->nullable()->after('platforms');
          $table->text('primary_image_url')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
          $table->dropColumn(['platforms', 'description', 'primary_image_url']);
        });
    }
};
