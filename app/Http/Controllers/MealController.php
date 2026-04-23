<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

class MealController extends Controller
{
    /**
     * GET /meals
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Base query without unnecessary relationships
            $query = Meal::query();

            // Show system meals to all users + custom meals only for the creator
            $query->where(function($q) use ($user) {
                $q->where('is_system', true)
                  ->orWhere(function($subQuery) use ($user) {
                      $subQuery->where('is_custom', true)
                               ->where('user_id', $user->id);
                  });
            });

            // Apply additional filters
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

            // Format the data to show only required fields
            $formattedData = collect($meals->items())->map(function ($meal) {
                return [
                    'id' => $meal->id,
                    'name' => $meal->name,
                    'category_id' => $meal->category_id,
                    'user_id' => $meal->user_id,
                    'is_system' => $meal->is_system,
                    'is_custom' => $meal->is_custom,
                    'description' => $meal->description,
                    'meal_type' => $meal->meal_type,
                    'image_url' => $meal->image_url,
                ];
            });

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meals fetched successfully',
                'data' => $formattedData,
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
                'description' => 'nullable|string',
                'meal_type' => 'required|in:Breakfast,Lunch,Dinner',
                'image' => 'nullable|image|max:5120', // 5MB
            ]);

            $user = Auth::user();
            $validated['user_id'] = $user->id;

            // Check if user is admin and set flags accordingly
            if ($user->role === 'admin') {
                $validated['is_system'] = true;
                $validated['is_custom'] = false;
            } else {
                $validated['is_system'] = false;
                $validated['is_custom'] = true;
            }

            // Upload image to Cloudinary
            if ($request->hasFile('image')) {
                $validated['image_url'] = $cloudinary->upload(
                    $request->file('image'),
                    'meals'
                );
            }

            $meal = Meal::create($validated);

            // Format the response
            $formattedMeal = [
                'id' => $meal->id,
                'name' => $meal->name,
                'category_id' => $meal->category_id,
                'user_id' => $meal->user_id,
                'is_system' => $meal->is_system,
                'is_custom' => $meal->is_custom,
                'description' => $meal->description,
                'meal_type' => $meal->meal_type,
                'image_url' => $meal->image_url,
            ];

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Meal created successfully',
                'data' => $formattedMeal
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
            $user = Auth::user();
            
            // Check if user can access this meal
            if (!$meal->is_system && $meal->user_id !== $user->id) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'You do not have permission to view this meal',
                    'data' => null
                ], 403);
            }

            // Format the data
            $formattedMeal = [
                'id' => $meal->id,
                'name' => $meal->name,
                'category_id' => $meal->category_id,
                'user_id' => $meal->user_id,
                'is_system' => $meal->is_system,
                'is_custom' => $meal->is_custom,
                'description' => $meal->description,
                'meal_type' => $meal->meal_type,
                'image_url' => $meal->image_url,
            ];

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal fetched successfully',
                'data' => $formattedMeal
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

    /**
     * PUT /meals/{meal}
     */
    public function update(
        Request $request,
        Meal $meal,
        CloudinaryService $cloudinary
    ): JsonResponse {
        try {
            $user = Auth::user();
            
            // Check if user can update this meal
            $isAdmin = $user->role === 'admin';
            
            if (!$isAdmin && $meal->user_id !== $user->id) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'You do not have permission to update this meal',
                    'data' => null
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'category_id' => 'sometimes|exists:categories,id',
                'description' => 'nullable|string',
                'meal_type' => 'sometimes|in:Breakfast,Lunch,Dinner',
                'image' => 'nullable|image|max:5120', // 5MB
            ]);

            // Upload new image if provided
            if ($request->hasFile('image')) {
                $validated['image_url'] = $cloudinary->upload(
                    $request->file('image'),
                    'meals'
                );
            }

            $meal->update($validated);

            // Format the response
            $formattedMeal = [
                'id' => $meal->id,
                'name' => $meal->name,
                'category_id' => $meal->category_id,
                'user_id' => $meal->user_id,
                'is_system' => $meal->is_system,
                'is_custom' => $meal->is_custom,
                'description' => $meal->description,
                'meal_type' => $meal->meal_type,
                'image_url' => $meal->image_url,
            ];

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Meal updated successfully',
                'data' => $formattedMeal
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

    /**
     * DELETE /meals/{meal}
     */
    public function destroy(Meal $meal): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user can delete this meal
            $isAdmin = $user->role === 'admin';
            
            if (!$isAdmin && $meal->user_id !== $user->id) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'You do not have permission to delete this meal',
                    'data' => null
                ], 403);
            }

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

    
    public function saveAiMeal(
    Request $request,
    CloudinaryService $cloudinary
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string',
                'meal_type'   => 'required|in:Breakfast,Lunch,Dinner',
                'category_id' => 'required|integer|exists:categories,id',
                'image_url'   => 'nullable|string',
            ]);

            $imageUrl = null;

            // Download from Unsplash and upload to Cloudinary
            if (!empty($validated['image_url'])) {
                try {
                    $imageContents = file_get_contents($validated['image_url']);
                    $tempPath = tempnam(sys_get_temp_dir(), 'ai_meal_') . '.jpg';
                    file_put_contents($tempPath, $imageContents);

                    $uploadedFile = new \Illuminate\Http\UploadedFile(
                        $tempPath,
                        'ai_meal.jpg',
                        'image/jpeg',
                        null,
                        true
                    );

                    $imageUrl = $cloudinary->upload($uploadedFile, 'meals');
                    @unlink($tempPath); // cleanup temp file

                } catch (\Exception $e) {
                    // fallback to original URL if upload fails
                    $imageUrl = $validated['image_url'];
                    \Log::warning('Cloudinary upload failed, using original URL', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $meal = Meal::create([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
                'meal_type'   => $validated['meal_type'],
                'category_id' => $validated['category_id'],
                'user_id'     => Auth::id(),
                'image_url'   => $imageUrl,
                'is_system'   => false,
                'is_custom'   => true,
            ]);

            return response()->json([
                'response_code' => 201,
                'status'        => 'success',
                'message'       => 'AI meal saved successfully',
                'data'          => [
                    'id'          => $meal->id,
                    'name'        => $meal->name,
                    'description' => $meal->description,
                    'meal_type'   => $meal->meal_type,
                    'category_id' => $meal->category_id,
                    'user_id'     => $meal->user_id,
                    'is_system'   => $meal->is_system,
                    'is_custom'   => $meal->is_custom,
                    'image_url'   => $meal->image_url,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to save AI meal',
                'error'         => $e->getMessage(),
            ], 500);
        }
    }

}