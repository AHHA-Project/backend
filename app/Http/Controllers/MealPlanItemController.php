<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MealPlanItem;
use App\Models\DailyMealPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

class MealPlanItemController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'planner_id' => 'required|exists:daily_meal_plans,id',
                'meal_id' => 'required|exists:meals,id',
                'meal_role' => 'required|in:Breakfast,Lunch,Dinner',
                'meal_time' => 'required|date_format:H:i',
            ]);

            $plan = DailyMealPlan::findOrFail($validated['planner_id']);
            if ($plan->user_id !== Auth::user()->id) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $item = MealPlanItem::create($validated);

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Meal plan item created successfully',
                'data' => $item->load(['meal.category', 'meal.user'])
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status' => 'error',
                'message' => 'Validation error',
                'data' => null,
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to create meal plan item',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, MealPlanItem $mealPlanItem): JsonResponse
    {
        try {
            $mealPlanItem->load('dailyMealPlan');
            if ($mealPlanItem->dailyMealPlan->user_id !== Auth::user()->id) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $validated = $request->validate([
                'meal_id' => 'sometimes|exists:meals,id',
                'meal_role' => 'sometimes|in:Breakfast,Lunch,Dinner',
                'meal_time' => 'sometimes|date_format:H:i',
            ]);

            $mealPlanItem->update($validated);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal plan item updated successfully',
                'data' => $mealPlanItem->load(['meal.category', 'meal.user'])
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status' => 'error',
                'message' => 'Validation error',
                'data' => null,
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to update meal plan item',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(MealPlanItem $mealPlanItem): JsonResponse
    {
        try {
            $mealPlanItem->load('dailyMealPlan');
            if ($mealPlanItem->dailyMealPlan->user_id !== Auth::user()->id) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $mealPlanItem->delete();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal plan item deleted successfully',
                'data' => null
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to delete meal plan item',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
