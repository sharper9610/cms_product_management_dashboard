<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sku_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')
                ->constrained('sku_mapping_webhooks')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('parent_sku');
            $table->json('child_skus');
            $table->timestamp('mapped_at')->nullable();
            $table->timestamps();

            $table->unique('parent_sku');
            $table->index('parent_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_mappings');
    }
};
