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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Shopify identifiers
            $table->string('shopify_customer_id')->unique();
            $table->string('shopify_legacy_id')->nullable()->index();

            // Core identity
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('phone')->nullable();

            // Shopify data
            $table->string('locale', 10)->default('en');
            $table->string('state')->default('ENABLED');
            $table->boolean('tax_exempt')->default(false);
            $table->boolean('verified_email')->default(false);
            $table->text('note')->nullable();
            $table->json('tags')->nullable();

            // Business metrics
            $table->decimal('amount_spent', 10, 2)->default(0);
            $table->integer('number_of_orders')->default(0);

            // Timestamps
            $table->timestamp('shopify_created_at')->nullable();
            $table->timestamp('shopify_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('state');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
