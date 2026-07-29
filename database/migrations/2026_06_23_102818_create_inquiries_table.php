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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            // Context keys
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');   // The inquiring customer
    $table->foreignId('store_id')->constrained('stores')->onDelete('cascade'); // The targeted shop
    
    // Threading feature (Self-referencing parent ID)
    // If parent_id is NULL, it's the original customer inquiry. 
    // If parent_id matches an existing inquiry ID, it's the vendor's reply.
    $table->foreignId('parent_id')->nullable()->constrained('inquiries')->onDelete('cascade');
    
    // Content details
    $table->string('subject')->nullable(); // e.g., "Bulk order inquiry for Rare Ferns"
    $table->text('message');
    
    // Status flags
    $table->boolean('is_read')->default(false); // Helpful for vendor notifications
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
