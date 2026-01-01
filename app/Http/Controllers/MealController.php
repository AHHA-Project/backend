<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class MealController extends Controller
{
    /**
     * GET /meals
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Meal::with(['category', 'user']);

            if ($request->filled('meal_type')) {
                $query->where('meal_type', $request->meal_type);
            }

            if ($request->has('is_system')) {
                $query->where('is_system', $request->boolean('is_system'));
            }

            if ($request->has('is_custom')) {
                $query->where('is_custom', $request->boolean('is_custom'));
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            $meals = $query->latest()->paginate($request->get('per_page', 15));

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
                ],
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch meals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /meals (with image upload)
     */
    public function store(
        Request $request,
        CloudinaryService $cloudinary
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'meal_type' => 'required|in:Breakfast,Lunch,Dinner',
                'description' => 'nullable|string',
                'image' => 'nullable|image|max:5120', // 5MB
            ]);

            $validated['user_id'] = auth()->id() ?? 1;
            $validated['is_custom'] = true;
            $validated['is_system'] = false;

            // ✅ Upload image to Cloudinary
            if ($request->hasFile('image')) {
                $validated['image_url'] = $cloudinary->upload(
                    $request->file('image'),
                    'meals'
                );
            }

            $meal = Meal::create($validated);

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Meal created successfully',
                'data' => $meal->load(['category', 'user']),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to create meal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /meals/{meal}
     */
    public function show(Meal $meal): JsonResponse
    {
        try {
            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal fetched successfully',
                'data' => $meal->load(['category', 'user']),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch meal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /meals/{meal}
     */
    public function update(
        Request $request,
        Meal $meal,
        CloudinaryService $cloudinary
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'category_id' => 'sometimes|exists:categories,id',
                'meal_type' => 'sometimes|in:Breakfast,Lunch,Dinner',
                'description' => 'nullable|string',
                'is_system' => 'sometimes|boolean',
                'is_custom' => 'sometimes|boolean',
                'image' => 'nullable|image|max:5120',
            ]);

            if ($request->hasFile('image')) {
                $validated['image_url'] = $cloudinary->upload(
                    $request->file('image'),
                    'meals'
                );
            }

            $meal->update($validated);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal updated successfully',
                'data' => $meal->load(['category', 'user']),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to update meal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /meals/{meal}
     */
    public function destroy(Meal $meal): JsonResponse
    {
        try {
            $meal->delete();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal deleted successfully',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to delete meal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
