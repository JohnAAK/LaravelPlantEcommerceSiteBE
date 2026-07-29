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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            // Using user_id to match User <-> Store Eloquent defaults
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('location')->nullable();
            
            // JSON type to support the model's 'array' cast
            $table->json('contact_info')->nullable(); 
            
            // Flexible status workflow (pending, approved, rejected)
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();

            // Indexing for search performance
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};