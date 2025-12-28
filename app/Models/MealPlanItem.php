<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'planner_id',
        'meal_id',
        'meal_role',
        'meal_time',
    ];

    protected $casts = [
        'meal_time' => 'datetime:H:i',
    ];

    public function dailyMealPlan(): BelongsTo
    {
        return $this->belongsTo(DailyMealPlan::class, 'planner_id');
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }

    // Scope for specific meal role
    public function scopeOfRole($query, $role)
    {
        return $query->where('meal_role', $role);
    }

    // Scope for breakfast
    public function scopeBreakfast($query)
    {
        return $query->where('meal_role', 'Breakfast');
    }

    // Scope for lunch
    public function scopeLunch($query)
    {
        return $query->where('meal_role', 'Lunch');
    }

    // Scope for dinner
    public function scopeDinner($query)
    {
        return $query->where('meal_role', 'Dinner');
    }
}