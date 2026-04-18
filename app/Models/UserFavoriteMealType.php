<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFavoriteMealType extends Model
{
    use HasFactory;

    protected $table = 'user_favorite_meal_types';

    protected $fillable = [
        'user_id',
        'meal_type',
    ];
}