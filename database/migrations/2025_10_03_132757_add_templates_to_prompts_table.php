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
        Schema::table('prompts', function (Blueprint $table) {
            $table->longText('template_gift_card')->nullable()->after('template_es');
            $table->longText('template_gift_card_pt')->nullable()->after('template_gift_card');
            $table->longText('template_gift_card_es')->nullable()->after('template_gift_card_pt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            $table->dropColumn(['template_gift_card', 'template_gift_card_pt', 'template_gift_card_es']);
        });
    }
};
