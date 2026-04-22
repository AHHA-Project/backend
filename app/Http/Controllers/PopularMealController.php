<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Meal;
use App\Models\MealPlanItem;

class PopularMealController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $meals = Meal::with('category')
                // ->where('user_id', '!=', $user->id) // exclude own meals
                ->withCount(['mealPlanItems as popularity']) // count how many times added to plans
                ->having('popularity', '>', 0) // only meals that have been planned
                ->orderByDesc('popularity') // most popular first
                ->take(10)
                ->get()
                ->map(function ($meal) {
                    return [
                        'id'          => $meal->id,
                        'name'        => $meal->name,
                        'description' => $meal->description,
                        'meal_type'   => $meal->meal_type,
                        'image_url'   => $meal->image_url,
                        'is_system'   => $meal->is_system,
                        'is_custom'   => $meal->is_custom,
                        'category'    => $meal->category,
                        'popularity'  => $meal->popularity, // ← plan count
                    ];
                });

            // fallback if no popular meals yet
            if ($meals->isEmpty()) {
                $meals = Meal::with('category')
                    // ->where('user_id', '!=', $user->id)
                    ->where('is_system', true)
                    ->latest()
                    ->take(10)
                    ->get()
                    ->map(function ($meal) {
                        return [
                            'id'          => $meal->id,
                            'name'        => $meal->name,
                            'description' => $meal->description,
                            'meal_type'   => $meal->meal_type,
                            'image_url'   => $meal->image_url,
                            'is_system'   => $meal->is_system,
                            'is_custom'   => $meal->is_custom,
                            'category'    => $meal->category,
                            'popularity'  => 0,
                        ];
                    });
            }

            return response()->json([
                'status' => 'success',
                'data'   => $meals,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch popular meals',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}