<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuthenticationController extends Controller
{
    /**
     * Register a new account.
     */
    public function register(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'sometimes|in:user,admin', // Optional, defaults to 'user'
        ]);

        try {
            $role = $validated['role'] ?? 'user';
            
            // For admin: auto-verify email, no OTP needed
            if ($role === 'admin') {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'admin',
                    'email_verified_at' => Carbon::now(), // Auto-verify admin
                    'otp' => null,
                    'otp_expires_at' => null
                ]);

                Log::info('Admin user created', ['user_id' => $user->id]);

                // Create token
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'response_code' => 201,
                    'status' => 'success',
                    'message' => 'Admin registration successful. You can login immediately.',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'email_verified_at' => $user->email_verified_at,
                    ],
                    'token' => $token,
                    'email_verified' => true,
                ], 201);
            }

            // For regular users: send OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'user',
                'otp' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(10)
            ]);

            Log::info('User created', ['user_id' => $user->id, 'role' => $user->role]);

            // Send OTP via email
            $user->notify(new SendOtpNotification($otp));

            Log::info('OTP sent to user', ['email' => $user->email]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Registration successful. Please check your email for the verification code.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'token' => $token,
                'email_verified' => false,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify OTP for email verification.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'response_code' => 404,
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            // Check if already verified
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'Email already verified',
                ], 400);
            }

            // Admins should not need OTP verification
            if ($user->role === 'admin') {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'Admin accounts do not require OTP verification',
                ], 400);
            }

            // Check OTP expiration
            if (!$user->otp_expires_at || Carbon::now()->isAfter($user->otp_expires_at)) {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'OTP has expired. Please request a new one.',
                ], 400);
            }

            // Verify OTP
            if ($user->otp !== $request->otp) {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'Invalid OTP',
                ], 400);
            }

            // Mark email as verified
            $user->email_verified_at = Carbon::now();
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->save();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Email verified successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('OTP Verification Error', ['message' => $e->getMessage()]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Verification failed',
            ], 500);
        }
    }

    /**
     * Resend OTP for email verification.
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'Email already verified',
                ], 400);
            }

            // Admins don't need OTP
            if ($user->role === 'admin') {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'Admin accounts do not require OTP verification',
                ], 400);
            }

            // Generate new OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->otp = $otp;
            $user->otp_expires_at = Carbon::now()->addMinutes(10);
            $user->save();

            // Send OTP
            $user->notify(new SendOtpNotification($otp));

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'OTP sent successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Resend OTP Error', ['message' => $e->getMessage()]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to send OTP',
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

                // Admins can login without email verification
                if ($user->role === 'admin') {
                    $accessToken = $user->createToken('authToken')->plainTextToken;

                    return response()->json([
                        'response_code' => 200,
                        'status' => 'success',
                        'message' => 'Admin login successful',
                        'user_info' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role,
                            'email_verified_at' => $user->email_verified_at,
                            'profile_img' => $user->profile_img,
                        ],
                        'token' => $accessToken,
                    ]);
                }

                // Regular users must verify email
                if (!$user->hasVerifiedEmail()) {
                    Auth::logout();

                    return response()->json([
                        'response_code' => 403,
                        'status' => 'error',
                        'message' => 'Please verify your email address before logging in.',
                        'needs_verification' => true
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
                        'role' => $user->role,
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
            Log::error('Login Error', ['message' => $e->getMessage()]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Login failed',
            ], 500);
        }
    }

    /**
     * Get current authenticated user info.
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'User information retrieved successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                    'profile_img' => $user->profile_img,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get User Info Error', ['message' => $e->getMessage()]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch user information',
            ], 500);
        }
    }


    /**
     * Logout the user and revoke token.
     */
    public function logout(Request $request)
    {
        try {
            // Delete current access token
            $request->user()->tokens()->delete();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);

        } catch (\Exception $e) {
            Log::error('Logout Error', ['message' => $e->getMessage()]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'An error occurred during logout',
            ], 500);
        }
    }

    /**
     * Logout from all devices.
     */
    public function logoutAllDevices(Request $request)
    {
        try {
            // Delete all tokens
            $request->user()->tokens()->delete();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Successfully logged out from all devices',
            ]);

        } catch (\Exception $e) {
            Log::error('Logout All Devices Error', ['message' => $e->getMessage()]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'An error occurred during logout',
            ], 500);
        }
    }
}