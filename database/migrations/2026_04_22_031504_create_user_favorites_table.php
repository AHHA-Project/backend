<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('meal_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // prevent duplicate favorites
            $table->unique(['user_id', 'meal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
    }
};