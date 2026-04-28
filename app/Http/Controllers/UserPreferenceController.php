<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserFavoriteMealType;

class UserPreferenceController extends Controller
{
    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 401);
            }

            \Log::info('PREFERENCES INPUT', $request->all());
            \Log::info('USER', ['id' => $user->id]);

            // delete old data
            $user->favoriteCategories()->detach();
            UserFavoriteMealType::where('user_id', $user->id)->delete();

            // save categories
            if (!empty($request->categories)) {
                \Log::info('SAVING CATEGORIES', $request->categories);
                $user->favoriteCategories()->attach($request->categories);
            }

            // save meal types
            if (!empty($request->meal_types)) {
                \Log::info('SAVING MEAL TYPES', $request->meal_types);
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

        } catch (\Exception $e) {
            \Log::error('PREFERENCES ERROR', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}