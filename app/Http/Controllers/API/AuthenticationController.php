<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthenticationController extends Controller
{
    /**
     * Register a new account.
     */
    // public function register(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|min:4',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:8',
    //     ]);

    //     try {
    //         $user = new User;
    //         $user->name = $request->name;
    //         $user->email = $request->email;
    //         $user->password = Hash::make($request->password);
    //         $user->save();

    //         return response()->json([
    //             'response_code' => 201,
    //             'status' => 'success',
    //             'message' => 'Successfully registered',
    //         ], 201);

    //     } catch (\Exception $e) {
    //         Log::error('Registration Error: '.$e->getMessage());

    //         return response()->json([
    //             'response_code' => 500,
    //             'status' => 'error',
    //             'message' => 'Registration failed',
    //         ], 500);
    //     }
    // }

    public function register(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        try {
            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Log to verify user was created
            Log::info('User created: '.$user->id);

            // Fire the Registered event to send verification email
            event(new \Illuminate\Auth\Events\Registered($user));

            // Log to verify event was fired
            Log::info('Registered event fired for user: '.$user->email);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Registration successful. Please check your email to verify your account.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'token' => $token,
                'email_verified' => false,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration Error: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Registration failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();

                // Check if email is verified
                if (! $user->hasVerifiedEmail()) {
                    Auth::logout();

                    return response()->json([
                        'response_code' => 403,
                        'status' => 'error',
                        'message' => 'Please verify your email address before logging in. Check your inbox for the verification link.',
                    ], 403);
                }

                $accessToken = $user->createToken('authToken')->plainTextToken;

                return response()->json([
                    'response_code' => 200,
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user_info' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                    ],
                    'token' => $accessToken,
                ]);
            }

            return response()->json([
                'response_code' => 401,
                'status' => 'error',
                'message' => 'Invalid email or password',
            ], 401);

        } catch (\Exception $e) {
            Log::error('Login Error: '.$e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Login failed',
            ], 500);
        }
    }

    /**
     * Get paginated user list (authenticated).
     */
    public function userInfo()
    {
        try {
            $users = User::latest()->paginate(10);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Fetched user list successfully',
                'data_user_list' => $users,
            ]);
        } catch (\Exception $e) {
            Log::error('User List Error: '.$e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch user list',
            ], 500);
        }
    }

    /**
     * Logout the user and revoke token.
     */
    public function logOut(Request $request)
    {
        try {
            if (Auth::check()) {
                Auth::user()->tokens()->delete();

                return response()->json([
                    'response_code' => 200,
                    'status' => 'success',
                    'message' => 'Successfully logged out',
                ]);
            }

            return response()->json([
                'response_code' => 401,
                'status' => 'error',
                'message' => 'User not authenticated',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Logout Error: '.$e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'An error occurred during logout',
            ], 500);
        }
    }
}
