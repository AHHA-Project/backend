<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'user_id',
        'is_system',
        'is_custom',
        'description',
        'meal_type',
        'image_url',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_custom' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mealPlanItems(): HasMany
    {
        return $this->hasMany(MealPlanItem::class);
    }

    // Scope for system meals
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    // Scope for custom meals
    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    // Scope for specific meal type
    public function scopeOfType($query, $type)
    {
        return $query->where('meal_type', $type);
    }
}