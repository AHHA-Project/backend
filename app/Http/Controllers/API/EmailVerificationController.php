<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    /**
     * Verify email from link
     */
    public function verify(Request $request, $id, $hash)
    {
        try {
            // Find the user
            $user = User::findOrFail($id);
            
            // Verify the hash matches
            if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Invalid verification link'
                ], 403);
            }
            
            // Check if already verified
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'response_code' => 200,
                    'status' => 'success',
                    'message' => 'Email already verified'
                ], 200);
            }
            
            // Mark email as verified
            $user->markEmailAsVerified();
            
            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Email verified successfully! You can now log in.',
                'verified' => true
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Email Verification Error: ' . $e->getMessage());
            
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Verification failed'
            ], 500);
        }
    }
    
    /**
     * Show verification notice
     */
    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Email already verified',
                'verified' => true
            ], 200);
        }
        
        return response()->json([
            'response_code' => 403,
            'status' => 'error',
            'message' => 'Please verify your email address',
            'verified' => false
        ], 403);
    }
    
    /**
     * Resend verification email
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Email already verified'
            ], 200);
        }
        
        $request->user()->sendEmailVerificationNotification();
        
        return response()->json([
            'response_code' => 200,
            'status' => 'success',
            'message' => 'Verification link sent to your email'
        ], 200);
    }
}