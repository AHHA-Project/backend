<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiMealController extends Controller
{

    // GENERATE MEAL USING GEMINI AI

    public function generateMeal(Request $request)
    {
        try {
            $request->validate([
                'keywords'  => 'nullable|string|max:200',
                'meal_type' => 'nullable|in:Breakfast,Lunch,Dinner',
                'category'  => 'nullable|string|max:100',
            ]);

            $keywords  = $request->input('keywords', '');
            $mealType  = $request->input('meal_type', 'Lunch');
            $category  = $request->input('category', '');


            // STEP 1 — Ask Gemini to generate meal details

            $prompt = "You are a creative chef assistant. Generate a meal based on these preferences:
- Keywords: {$keywords}
- Meal Type: {$mealType}
- Category/Ingredient: {$category}

Respond ONLY with a valid JSON object, no markdown, no explanation, no backticks, just raw JSON:
{
  \"name\": \"meal name here\",
  \"description\": \"2-3 sentence appetizing description\",
  \"meal_type\": \"{$mealType}\",
  \"search_keyword\": \"simple 1-2 word keyword to search for food image e.g. fried chicken\"
}";

            $geminiResponse = Http::post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . env('GEMINI_API_KEY'),
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.8,
                        'maxOutputTokens' => 500,
                    ],
                ]
            );

            if (!$geminiResponse->successful()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Failed to generate meal from AI',
                    'error'   => $geminiResponse->body(),
                ], 500);
            }

            $geminiData = $geminiResponse->json();
            $rawText    = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // clean JSON response
            $cleanJson = preg_replace('/```json|```/', '', $rawText);
            $mealData  = json_decode(trim($cleanJson), true);

            if (!$mealData) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Failed to parse AI response',
                    'raw'     => $rawText,
                ], 500);
            }

            // STEP 2 — Search Unsplash for food image
            $searchKeyword = $mealData['search_keyword'] ?? $mealData['name'];

            $unsplashResponse = Http::withHeaders([
                'Authorization' => 'Client-ID ' . env('UNSPLASH_ACCESS_KEY'),
            ])->get('https://api.unsplash.com/search/photos', [
                'query'       => $searchKeyword . ' food',
                'per_page'    => 4,
                'orientation' => 'landscape',
            ]);

            $images = [];
            if ($unsplashResponse->successful()) {
                $unsplashData = $unsplashResponse->json();
                $images = collect($unsplashData['results'] ?? [])
                    ->map(fn($photo) => [
                        'id'    => $photo['id'],
                        'url'   => $photo['urls']['regular'],
                        'thumb' => $photo['urls']['small'],
                        'author' => $photo['user']['name'] ?? '',
                    ])
                    ->toArray();
            }

            // STEP 3 — Return generated meal + images
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'name'        => $mealData['name'],
                    'description' => $mealData['description'],
                    'meal_type'   => $mealData['meal_type'] ?? $mealType,
                    'images'      => $images,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to generate meal',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}