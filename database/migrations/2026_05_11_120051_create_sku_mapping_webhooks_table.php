<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sku_mapping_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->string('label')->nullable();
            $table->string('kind')->nullable();
            $table->string('scope')->nullable();
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_mapping_webhooks');
    }
};
