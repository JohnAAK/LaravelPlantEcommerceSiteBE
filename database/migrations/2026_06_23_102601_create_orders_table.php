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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('coupon_id')->nullable()->constrained('coupons');
            $table->decimal('total_amount', 8, 2);
            $table->decimal('discount_amount', 8, 2)->default(0.00);
            $table->string('payment_method'); // 'paystack' or 'delivery'
            $table->string('payment_status')->default('unpaid'); // 'unpaid', 'paid', 'refunded'
            $table->string('order_status')->default('pending'); // 'pending', 'processing', 'shipped', 'delivered', 'cancelled'
            $table->text('delivery_address');
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
