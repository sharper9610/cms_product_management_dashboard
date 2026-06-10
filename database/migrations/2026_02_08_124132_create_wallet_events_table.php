<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            
            // Event type
            $table->enum('type', [
                'TOPUP',
                'BONUS',
                'PURCHASE',
                'WITHDRAWAL',
                'TOURNAMENT_WIN',
                'REFUND',
                'ADJUSTMENT',
            ]);
            
            // Balance changes (delta - can be positive or negative)
            $table->decimal('rib_cash_delta', 12, 2)->default(0);
            $table->decimal('topup_cash_delta', 12, 2)->default(0);
            $table->decimal('bonus_delta', 12, 2)->default(0);
            
            // Balance snapshots (after this event)
            $table->decimal('rib_cash_balance', 12, 2)->default(0);
            $table->decimal('topup_cash_balance', 12, 2)->default(0);
            $table->decimal('bonus_balance', 12, 2)->default(0);
            
            // Reference information
            $table->string('reference_type', 50)->nullable()->comment('shopify, rib, admin, system');
            $table->string('reference_id', 255)->nullable()->comment('Order ID, transaction ID, etc.');
            
            // Metadata
            $table->json('metadata')->nullable()->comment('Additional context');
            $table->text('description')->nullable();
            
            // Audit
            $table->string('created_by', 100)->nullable()->comment('User/system that created this event');
            $table->ipAddress('ip_address')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallets')
                ->onDelete('cascade');
            
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['wallet_id', 'created_at']);
            $table->index(['customer_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_events');
    }
};