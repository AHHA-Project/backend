<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('creator (admin or user)');
            $table->boolean('is_system')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->text('description')->nullable();
            $table->enum('meal_type', ['Breakfast', 'Lunch', 'Dinner']);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};