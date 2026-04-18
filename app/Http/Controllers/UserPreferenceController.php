<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserFavoriteMealType;

class UserPreferenceController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }

        // delete old data
        $user->favoriteCategories()->detach();
        UserFavoriteMealType::where('user_id', $user->id)->delete();

        // save categories
        if (!empty($request->categories)) {
            $user->favoriteCategories()->attach($request->categories);
        }

        // save meal types
        if (!empty($request->meal_types)) {
            foreach ($request->meal_types as $type) {
                UserFavoriteMealType::create([
                    'user_id' => $user->id,
                    'meal_type' => $type,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Preferences saved successfully'
        ]);
    }
}   