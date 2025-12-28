<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('daily_meal_plans')->onDelete('cascade');
            $table->foreignId('meal_id')->constrained()->onDelete('cascade');
            $table->enum('meal_role', ['Breakfast', 'Lunch', 'Dinner']);
            $table->time('meal_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_items');
    }
};