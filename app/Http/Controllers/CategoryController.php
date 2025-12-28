<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            // Try to load meals count, but handle if relationship doesn't exist
            $categories = Category::all();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Categories fetched successfully',
                'data' => $categories
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch categories',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
            ]);

            $category = Category::create($validated);

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => $category
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
                'message' => 'Failed to create category',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Category $category): JsonResponse
    {
        try {
            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Category fetched successfully',
                'data' => $category
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch category',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            ]);

            $category->update($validated);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => $category
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
                'message' => 'Failed to update category',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $category->delete();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Category deleted successfully',
                'data' => null
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to delete category',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}