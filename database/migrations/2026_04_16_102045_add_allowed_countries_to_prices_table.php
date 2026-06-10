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
            Schema::table('prices', function (Blueprint $table) {
                $table->text('allowed_countries')
                    ->nullable()
                    ->after('country_code');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            Schema::table('prices', function (Blueprint $table) {
                $table->dropColumn('allowed_countries');
            });
        });
    }
};
