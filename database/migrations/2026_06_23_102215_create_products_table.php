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
           
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories');
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 8, 2); // Handles numbers up to 999,999.99
            $table->integer('stock')->default(0);
            $table->boolean('is_indoor')->default(true);
            $table->string('light_needs');
            $table->string('watering_frequency');
            
            $table->timestamps();

            // Adding indexes for faster customer filtering performance
             $table->index(['price', 'category_id']);
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
