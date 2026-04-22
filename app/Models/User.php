<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Category;
use App\Models\UserFavoriteMealType;
use App\Models\DailyMealPlan;
use App\Models\MealPlanItem;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'otp',
        'otp_expires_at',
        'profile_img',
        'is_active',
        'deactivated_at',
        'deactivated_by',
        'activated_at',
        'activated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at'    => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'deactivated_at'    => 'datetime',
            'activated_at'      => 'datetime',
        ];
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is normal user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    // FAVORITE CATEGORIES RELATION
    public function favoriteCategories()
    {
        return $this->belongsToMany(Category::class, 'user_favorite_categories');
    }

    // FAVORITE MEAL TYPES RELATION
    public function favoriteMealTypes()
    {
        return $this->hasMany(UserFavoriteMealType::class);
    }

    // DAILY MEAL PLANS RELATION
    public function dailyMealPlans()
    {
        return $this->hasMany(DailyMealPlan::class);
    }

    // MEAL PLAN ITEMS (through daily meal plans) — used for Phase 2/3 recommendations
    public function mealPlanItems()
    {
        return $this->hasManyThrough(
            MealPlanItem::class,
            DailyMealPlan::class,
            'user_id',   
            'planner_id', 
            'id',         
            'id'          
        );
    }
}

/*
To add the admin
1. php artisan tinker
2. paste this
\App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password123'),
    'role' => 'admin',
    'email_verified_at' => now(),
]);
*/