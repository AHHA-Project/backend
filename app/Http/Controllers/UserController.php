<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Exception;

class UserController extends Controller
{
    /**
     * Get paginated user list (ADMIN ONLY).
     */
    public function userList(Request $request)
    {
        try {
            // Check if user is admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $perPage = $request->input('per_page', 10);
            $users = User::latest()->paginate($perPage);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Fetched user list successfully',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            Log::error('User List Error', ['message' => $e->getMessage()]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch user list',
            ], 500);
        }
    }

    /**
     * Update user role (ADMIN ONLY).
     */
    public function updateUserRole(Request $request, $userId)
    {
        try {
            // Check if user is admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $validated = $request->validate([
                'role' => 'required|in:user,admin',
            ]);

            $user = User::findOrFail($userId);

            // Prevent self-demotion
            if ($user->id === $request->user()->id) {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'You cannot change your own role',
                ], 400);
            }

            $oldRole = $user->role;
            $user->role = $validated['role'];

            // If promoting to admin, auto-verify email
            if ($validated['role'] === 'admin' && ! $user->hasVerifiedEmail()) {
                $user->email_verified_at = Carbon::now();
                $user->otp = null;
                $user->otp_expires_at = null;
            }

            $user->save();

            Log::info('User role updated', [
                'user_id' => $user->id,
                'old_role' => $oldRole,
                'new_role' => $user->role,
            ]);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'User role updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Update Role Error', ['message' => $e->getMessage()]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to update user role',
            ], 500);
        }
    }

    /**
     * Toggle user status (ADMIN ONLY).
     */
    public function toggleUserStatus(Request $request, $userId)
    {
        try {
            // Check if user is admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'response_code' => 403,
                    'status' => 'error',
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $user = User::findOrFail($userId);

            // Prevent toggling own account
            if ($user->id === $request->user()->id) {
                return response()->json([
                    'response_code' => 400,
                    'status' => 'error',
                    'message' => 'You cannot change your own account status',
                ], 400);
            }

            // Toggle status
            $newStatus = ! $user->is_active;
            $user->is_active = $newStatus;

            if ($newStatus === false) {
                // Deactivating
                $user->deactivated_at = now();
                $user->deactivated_by = $request->user()->id;
            } else {
                // Activating
                $user->activated_at = now();
                $user->activated_by = $request->user()->id;
                $user->deactivated_at = null;
                $user->deactivated_by = null;
            }

            $user->save();

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => $newStatus ? 'User activated successfully' : 'User deactivated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'is_active' => $user->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Toggle User Status Error', ['message' => $e->getMessage()]);

            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to update user status',
            ], 500);
        }
    }

    public function store(
        Request $request,
        CloudinaryService $cloudinary
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'profile_img' => 'nullable|image|max:5120', // 5MB
            ]);

            $user = Auth::user();
            $validated['user_id'] = $user->id;

            // Upload image to Cloudinary
            if ($request->hasFile('profile_img')) {
                $validated['profile_img'] = $cloudinary->upload(
                    $request->file('profile_img'),
                    'users'
                );
            }

            // Update the user's profile image
            $user->update($validated);

            // Format the response
            $formattedUser = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_img' => $user->profile_img,
            ];

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Profile image updated successfully',
                'data' => $formattedUser,
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
                'message' => 'Failed to update profile image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
