<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyMealPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_date',
    ];

    protected $casts = [
        'plan_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mealPlanItems(): HasMany
    {
        return $this->hasMany(MealPlanItem::class, 'planner_id');
    }

    // Scope for today's plan
    public function scopeToday($query)
    {
        return $query->whereDate('plan_date', today());
    }

    // Scope for a specific date
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('plan_date', $date);
    }

    // Scope for a user
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}