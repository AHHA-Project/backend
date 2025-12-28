<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_meal_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('plan_date');
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate plans for same user and date
            $table->unique(['user_id', 'plan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_meal_plans');
    }
};