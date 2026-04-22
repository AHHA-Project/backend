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
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $meals = $this->getRecommendations($user);

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

    private function getRecommendations($user)
    {
        // PHASE 2/3 — User has real meal plan history
        $hasRealData = $user->mealPlanItems()->exists();

        if ($hasRealData) {
            return $this->getDataDrivenRecommendations($user);
        }

        // PHASE 1 — Cold start: use onboarding prefs
        $categoryIds = $user->favoriteCategories()->pluck('categories.id')->toArray();
        $mealTypes   = $user->favoriteMealTypes()->pluck('meal_type')->toArray();

        $hasPreferences = !empty($categoryIds) || !empty($mealTypes);

        if ($hasPreferences) {
            return $this->getPreferenceBasedRecommendations($categoryIds, $mealTypes, $user);
        }

        // FALLBACK — No data, no preferences yet
        return Meal::with('category')
            ->where('is_system', true)
            ->latest()
            ->take(10)
            ->get();
    }

    // PHASE 1 — Onboarding preference matching
    private function getPreferenceBasedRecommendations(array $categoryIds, array $mealTypes, $user)
    {
        $query = Meal::with('category')
            ->where('user_id', '!=', $user->id); // exclude own meals

        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }

        if (!empty($mealTypes)) {
            $query->whereIn('meal_type', $mealTypes);
        }

        $meals = $query->take(10)->get();

        // fallback if still empty
        if ($meals->isEmpty()) {
            return Meal::with('category')
                ->where('is_system', true)
                ->latest()
                ->take(10)
                ->get();
        }

        return $meals;
    }

    // PHASE 2/3 — Real data driven recommendations
    private function getDataDrivenRecommendations($user)
    {
        // Step 1: get user's most used categories from real meal plan history
        $topCategoryIds = $user->mealPlanItems()
            ->join('meals', 'meal_plan_items.meal_id', '=', 'meals.id')
            ->selectRaw('meals.category_id, COUNT(*) as usage_count')
            ->groupBy('meals.category_id')
            ->orderByDesc('usage_count')
            ->limit(3)
            ->pluck('meals.category_id')
            ->toArray();

        // Step 2: get user's most used meal roles from real meal plan history
        $topMealTypes = $user->mealPlanItems()
            ->selectRaw('meal_role, COUNT(*) as usage_count')
            ->groupBy('meal_role')
            ->orderByDesc('usage_count')
            ->limit(2)
            ->pluck('meal_role')
            ->toArray();

        // Step 3: find popular meals from OTHER users matching same pattern
        // (both category + meal type match)
        $meals = Meal::with('category')
            ->where('user_id', '!=', $user->id)
            ->whereIn('category_id', $topCategoryIds)
            ->whereIn('meal_type', $topMealTypes)
            ->withCount(['mealPlanItems as popularity'])
            ->orderByDesc('popularity')
            ->take(10)
            ->get();

        // Step 4: fallback — category match only (drop meal type filter)
        if ($meals->isEmpty() && !empty($topCategoryIds)) {
            $meals = Meal::with('category')
                ->where('user_id', '!=', $user->id)
                ->whereIn('category_id', $topCategoryIds)
                ->withCount(['mealPlanItems as popularity'])
                ->orderByDesc('popularity')
                ->take(10)
                ->get();
        }

        // Step 5: final fallback — use onboarding preferences
        if ($meals->isEmpty()) {
            $categoryIds = $user->favoriteCategories()->pluck('categories.id')->toArray();
            $mealTypes   = $user->favoriteMealTypes()->pluck('meal_type')->toArray();
            return $this->getPreferenceBasedRecommendations($categoryIds, $mealTypes, $user);
        }

        return $meals;
    }
}