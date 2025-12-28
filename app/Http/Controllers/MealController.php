<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

class MealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Meal::with(['category', 'user']);

            if ($request->has('meal_type')) {
                $query->ofType($request->meal_type);
            }

            if ($request->has('is_system')) {
                $query->where('is_system', $request->boolean('is_system'));
            }

            if ($request->has('is_custom')) {
                $query->where('is_custom', $request->boolean('is_custom'));
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            $meals = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meals fetched successfully',
                'data' => $meals->items(),
                'pagination' => [
                    'current_page' => $meals->currentPage(),
                    'per_page' => $meals->perPage(),
                    'total' => $meals->total(),
                    'last_page' => $meals->lastPage(),
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch meals',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'is_system' => 'boolean',
                'is_custom' => 'boolean',
                'description' => 'nullable|string',
                'meal_type' => 'required|in:Breakfast,Lunch,Dinner',
                'image_url' => 'nullable|url|max:255',
            ]);

            $validated['user_id'] = Auth::user()->id;

            $meal = Meal::create($validated);

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Meal created successfully',
                'data' => $meal
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
                'message' => 'Failed to create meal',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Meal $meal): JsonResponse
    {
        try {
            $meal->load(['category', 'user']);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal fetched successfully',
                'data' => $meal
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch meal',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Meal $meal): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'category_id' => 'sometimes|exists:categories,id',
                'is_system' => 'sometimes|boolean',
                'is_custom' => 'sometimes|boolean',
                'description' => 'nullable|string',
                'meal_type' => 'sometimes|in:Breakfast,Lunch,Dinner',
                'image_url' => 'nullable|url|max:255',
            ]);

            $meal->update($validated);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal updated successfully',
                'data' => $meal->load(['category', 'user'])
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
                'message' => 'Failed to update meal',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Meal $meal): JsonResponse
    {
        try {
            $meal->delete();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal deleted successfully',
                'data' => null
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to delete meal',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
