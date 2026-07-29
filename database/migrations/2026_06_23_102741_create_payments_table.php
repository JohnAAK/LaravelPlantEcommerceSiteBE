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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
    
    // Payment breakdown
    $table->decimal('amount', 10, 2); 
    $table->decimal('platform_commission', 10, 2)->default(0.00); // Tracks internal ledger platform cut
    $table->decimal('vendor_payout', 10, 2)->default(0.00);      // Track what the vendor gets
    
    // Methods: 'paystack' or 'payment_on_delivery'
    $table->string('payment_method'); 
    
    // Statuses: 'pending', 'completed', 'failed', 'refunded'
    $table->string('status')->default('pending'); 
    
    // Paystack Specific Fields (Nullable because POD won't have them)
    $table->string('reference')->nullable()->unique(); // Paystack transaction reference string
    $table->json('gateway_response')->nullable();     // Stores the full webhook JSON payload for auditing
    
    $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
