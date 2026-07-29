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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // Foreign Key Constraints
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // The customer
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
    $table->foreignId('store_id')->constrained('stores')->onDelete('cascade'); // Cached for easy storefront store-rating queries
    $table->foreignId('order_id')->constrained('orders')->onDelete('cascade'); // Verifies the purchase connection
    
    // Review Content
    $table->unsignedTinyInteger('rating'); // 1 to 5 stars
    $table->text('comment')->nullable();
            $table->timestamps();

            // Indexing for faster storefront generation performance
    $table->index(['product_id', 'rating']);
    $table->index('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
