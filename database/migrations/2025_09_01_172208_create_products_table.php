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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sku')->unique();
            $table->string('name', 255)->nullable();
            $table->boolean('source');

            $table->text('auxiliary_field')->nullable();
            $table->text('bundled_products')->nullable();
            $table->text('classification')->nullable();
            $table->text('community_discussion')->nullable();
            $table->string('default_language', 16)->nullable();
            $table->string('developers')->nullable();
            $table->text('dlc_products')->nullable();
            $table->unsignedInteger('download_date')->nullable();
            $table->text('drm_type')->nullable();
            $table->text('face_value')->nullable();
            $table->text('genres')->nullable();
            $table->string('platform', 255)->nullable();
            $table->integer('publisher_id')->nullable();
            $table->string('publisher_name', 255)->nullable();
            $table->string('product_type', 64)->nullable();
            $table->text('redemption')->nullable();
            $table->text('redemption_field')->nullable();
            $table->string('region_tag', 255)->nullable();
            $table->unsignedInteger('release_date')->nullable();
            $table->boolean('status')->default(true);
            $table->text('supported_languages')->nullable();
            $table->text('systems')->nullable();
            $table->text('system_requirements')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->unsignedInteger('update_timestamp')->nullable();
            $table->text('validade')->nullable();
            $table->decimal('average_rating', 5, 1)->nullable();
            $table->unsignedInteger('total_reviews')->nullable();
            $table->boolean('skip_update')->default(false)->nullable();



          $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
