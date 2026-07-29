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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            // nullable because if store_id is null, it's a platform-wide coupon made by an Admin
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('type'); // 'fixed' or 'percentage'
            $table->decimal('value', 8, 2);
            $table->timestamp('expires_at');
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
