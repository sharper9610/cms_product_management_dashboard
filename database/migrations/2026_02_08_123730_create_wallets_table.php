<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->unique();

            $table->string('store_id', 50)->default('2game_br');

            $table->decimal('rib_cash', 12, 2)->default(0)->comment('Earned, withdrawable');
            $table->decimal('topup_cash', 12, 2)->default(0)->comment('Prepaid, non-withdrawable');
            $table->decimal('bonus', 12, 2)->default(0)->comment('Promotional, restricted');

            $table->decimal('total_balance', 12, 2)->storedAs('rib_cash + topup_cash + bonus');

            $table->string('currency', 3)->default('BRL');

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_transaction_at')->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

            // Indexes
            $table->index(['customer_id', 'store_id']);
            $table->index('is_active');

            // Unique constraint: one wallet per customer per store
            $table->unique(['customer_id', 'store_id'], 'unique_customer_store_wallet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
