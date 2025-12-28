<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DailyMealPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

class DailyMealPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DailyMealPlan::with(['mealPlanItems.meal.category', 'user'])
                ->forUser(Auth::user()->id);

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('plan_date', [$request->start_date, $request->end_date]);
            }

            if ($request->has('date')) {
                $query->forDate($request->date);
            }

            $plans = $query->orderBy('plan_date', 'desc')->paginate($request->get('per_page', 15));

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal plans fetched successfully',
                'data' => $plans->items(),
                'pagination' => [
                    'current_page' => $plans->currentPage(),
                    'per_page' => $plans->perPage(),
                    'total' => $plans->total(),
                    'last_page' => $plans->lastPage(),
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch meal plans',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'plan_date' => 'required|date',
                'meal_items' => 'required|array',
                'meal_items.*.meal_id' => 'required|exists:meals,id',
                'meal_items.*.meal_role' => 'required|in:Breakfast,Lunch,Dinner',
                'meal_items.*.meal_time' => 'required|date_format:H:i',
            ]);

            $plan = DailyMealPlan::firstOrCreate([
                'user_id' => Auth::user()->id,
                'plan_date' => $validated['plan_date'],
            ]);

            foreach ($validated['meal_items'] as $item) {
                $plan->mealPlanItems()->create($item);
            }

            DB::commit();

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Meal plan created successfully',
                'data' => $plan->load(['mealPlanItems.meal.category', 'user'])
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'response_code' => 422,
                'status' => 'error',
                'message' => 'Validation error',
                'data' => null,
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to create meal plan',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(DailyMealPlan $dailyMealPlan): JsonResponse
    {
        try {
            if ($dailyMealPlan->user_id !== Auth::user()->id) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal plan fetched successfully',
                'data' => $dailyMealPlan->load(['mealPlanItems.meal.category', 'user'])
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch meal plan',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, DailyMealPlan $dailyMealPlan): JsonResponse
    {
        DB::beginTransaction();
        try {
            if ($dailyMealPlan->user_id !== Auth::user()->id) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $validated = $request->validate([
                'plan_date' => 'sometimes|date',
                'meal_items' => 'sometimes|array',
                'meal_items.*.meal_id' => 'required|exists:meals,id',
                'meal_items.*.meal_role' => 'required|in:Breakfast,Lunch,Dinner',
                'meal_items.*.meal_time' => 'required|date_format:H:i',
            ]);

            if (isset($validated['plan_date'])) {
                $dailyMealPlan->update(['plan_date' => $validated['plan_date']]);
            }

            if (isset($validated['meal_items'])) {
                $dailyMealPlan->mealPlanItems()->delete();
                foreach ($validated['meal_items'] as $item) {
                    $dailyMealPlan->mealPlanItems()->create($item);
                }
            }

            DB::commit();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal plan updated successfully',
                'data' => $dailyMealPlan->load(['mealPlanItems.meal.category', 'user'])
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'response_code' => 422,
                'status' => 'error',
                'message' => 'Validation error',
                'data' => null,
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to update meal plan',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(DailyMealPlan $dailyMealPlan): JsonResponse
    {
        try {
            if ($dailyMealPlan->user_id !== Auth::user()->id) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $dailyMealPlan->delete();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal plan deleted successfully',
                'data' => null
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to delete meal plan',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function today(): JsonResponse
    {
        try {
            $plan = DailyMealPlan::with(['mealPlanItems.meal.category', 'user'])
                ->forUser(Auth::user()->id)
                ->today()
                ->first();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Today meal plan fetched successfully',
                'data' => $plan
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch today meal plan',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
