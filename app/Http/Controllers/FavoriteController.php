<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserFavorite;
use App\Models\Meal;

class FavoriteController extends Controller
{
    // GET /favorites — fetch all favorites for current user
    public function index()
    {
        try {
            $user = Auth::user();

            $favorites = UserFavorite::with('meal.category')
                ->where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn($fav) => $fav->meal)
                ->filter()
                ->values();

            return response()->json([
                'status' => 'success',
                'data'   => $favorites,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch favorites',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // POST /favorites — add meal to favorites
    public function store(Request $request)
    {
        try {
            $request->validate([
                'meal_id' => 'required|integer|exists:meals,id',
            ]);

            $user = Auth::user();

            // prevent duplicate
            $favorite = UserFavorite::firstOrCreate([
                'user_id' => $user->id,
                'meal_id' => $request->meal_id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Meal added to favorites',
                'data'    => $favorite,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add favorite',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE /favorites/{mealId} — remove meal from favorites
    public function destroy($mealId)
    {
        try {
            $user = Auth::user();

            UserFavorite::where('user_id', $user->id)
                ->where('meal_id', $mealId)
                ->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Meal removed from favorites',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove favorite',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // POST /favorites/toggle — toggle favorite
    public function toggle(Request $request)
    {
        try {
            $request->validate([
                'meal_id' => 'required|integer|exists:meals,id',
            ]);

            $user = Auth::user();

            $existing = UserFavorite::where('user_id', $user->id)
                ->where('meal_id', $request->meal_id)
                ->first();

            if ($existing) {
                $existing->delete();
                return response()->json([
                    'status'      => 'success',
                    'message'     => 'Meal removed from favorites',
                    'is_favorite' => false,
                ]);
            }

            UserFavorite::create([
                'user_id' => $user->id,
                'meal_id' => $request->meal_id,
            ]);

            return response()->json([
                'status'      => 'success',
                'message'     => 'Meal added to favorites',
                'is_favorite' => true,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle favorite',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}