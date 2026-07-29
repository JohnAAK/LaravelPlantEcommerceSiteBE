<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attribute Groups (e.g., Light Need, Water Need, Placement)
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "Light Requirement"
            $table->string('slug')->unique(); // e.g., "light-requirement"
            $table->timestamps();
        });

        // Attribute Values (e.g., Low Light, Partial Shade, Full Sun)
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value'); // e.g., "Low Light"
            $table->string('slug');
            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};