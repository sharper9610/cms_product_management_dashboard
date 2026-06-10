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
        Schema::create('customer_metafields', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('customer_id')->index();

            $table->string('shopify_metafield_id')->nullable()->unique();
            $table->string('namespace')->index();
            $table->string('key')->index();
            $table->text('value');
            $table->string('type')->default('string');

            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');


            $table->unique(['customer_id', 'namespace', 'key'], 'unique_customer_metafield');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_metafields');
    }
};
