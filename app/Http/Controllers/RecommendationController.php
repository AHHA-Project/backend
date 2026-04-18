<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Meal;

class RecommendationController extends Controller
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
            // get user preferences
            $categoryIds = $user->favoriteCategories()->pluck('categories.id')->toArray();
            $mealTypes = $user->favoriteMealTypes()->pluck('meal_type')->toArray();

            // base query
            $query = Meal::with('category');

            // filter by category
            if (!empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            }

            // filter by meal type
            if (!empty($mealTypes)) {
                $query->whereIn('meal_type', $mealTypes);
            }

            //  fallback if no preferences or no result
            $meals = $query->take(10)->get();

            if ($meals->isEmpty()) {
                $meals = Meal::with('category')->latest()->take(10)->get();
            }

            return response()->json([
                'status' => 'success',
                'data' => $meals
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}