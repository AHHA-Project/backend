<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OtpVerificationController extends Controller
{
    /**
     * Send OTP to user's email
     */
    public function sendOtp(Request $request)
    {
        try {
            $user = $request->user();

            // Check if already verified
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'response_code' => 200,
                    'status' => 'success',
                    'message' => 'Email already verified'
                ], 200);
            }

            // Check rate limiting - prevent sending OTP too frequently
            if ($user->otp_expires_at && Carbon::parse($user->otp_expires_at)->isFuture()) {
                $remainingTime = Carbon::parse($user->otp_expires_at)->diffInSeconds(now());
                
                if ($remainingTime > 540) { // If less than 1 minute passed (10 min - 9 min)
                    return response()->json([
                        'response_code' => 429,
                        'status' => 'error',
                        'message' => 'Please wait before requesting a new OTP',
                        'retry_after' => 60 - (600 - $remainingTime)
                    ], 429);
                }
            }

            // Generate 6-digit OTP
            $otp = random_int(100000, 999999);

            // Save OTP with expiration time (10 minutes)
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(10)
            ]);

            // Send OTP via email
            $user->notify(new SendOtpNotification($otp));

            Log::info('OTP sent to user: ' . $user->email);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'OTP sent to your email address',
                'expires_in' => 600 // seconds
            ], 200);

        } catch (\Exception $e) {
            Log::error('Send OTP Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'otp' => 'required|string|size:6'
        ]);

        try {
            $user = $request->user();

            // Check if already verified
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'response_code' => 200,
                    'status' => 'success',
                    'message' => 'Email already verified',
                    'verified' => true
                ], 200);
            }

            // Check if OTP exists
            if (!$user->otp) {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'No OTP found. Please request a new one.'
                ], 400);
            }

            // Check if OTP has expired
            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'OTP has expired. Please request a new one.'
                ], 400);
            }

            // Verify OTP
            if ($user->otp !== $validated['otp']) {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'Invalid OTP. Please try again.'
                ], 400);
            }

            // Mark email as verified and clear OTP
            $user->update([
                'email_verified_at' => Carbon::now(),
                'otp' => null,
                'otp_expires_at' => null
            ]);

            Log::info('Email verified for user: ' . $user->email);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Email verified successfully!',
                'verified' => true
            ], 200);

        } catch (\Exception $e) {
            Log::error('OTP Verification Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Verification failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Check verification status
     */
    public function checkStatus(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'response_code' => 200,
            'status' => 'success',
            'verified' => $user->hasVerifiedEmail(),
            'message' => $user->hasVerifiedEmail() 
                ? 'Email is verified' 
                : 'Email not verified'
        ], 200);
    }

    /**
     * Resend OTP (alias for sendOtp for backwards compatibility)
     */
    public function resend(Request $request)
    {
        return $this->sendOtp($request);
    }
}