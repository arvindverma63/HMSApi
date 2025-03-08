<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Add a new user with a specific role (Admin-only action).
     *
     * @OA\Post(
     *     path="/api/admin/users",
     *     tags={"Admin"},
     *     summary="Add a new user with a specific role",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="jane@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="role", type="string", example="doctor")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User added successfully and OTP sent"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=403, description="Unauthorized action")
     * )
     */
    public function addUsers(Request $request)
    {
        if (!$request->user() || !in_array($request->user()->role, ['admin'])) {
            return response()->json(['error' => 'Unauthorized. Only admins can add users.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:accountant,doctor,nurse,pathologist,radiologist,receptionist',
        ]);

        $hospitalId = "H" . time();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'hospitalId' => $hospitalId,
        ]);

        $this->sendOtp($user);

        return response()->json([
            'message' => 'User added successfully. OTP sent to the user\'s email.',
            'user' => $user
        ], 201);
    }

    /**
     * Get all users by their role (Admin-only action).
     *
     * @OA\Get(
     *     path="/api/admin/users/{role}",
     *     tags={"Admin"},
     *     summary="Get all users by their role",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="doctor")
     *     ),
     *     @OA\Response(response=200, description="List of users with the specified role"),
     *     @OA\Response(response=403, description="Unauthorized action"),
     *     @OA\Response(response=404, description="No users found for this role")
     * )
     */
    public function getUsersByRole(Request $request, $role)
    {
        if (!$request->user() || !in_array($request->user()->role, ['admin'])) {
            return response()->json(['error' => 'Unauthorized. Only admins can view users.'], 403);
        }

        // Validate the role parameter
        $validRoles = ['accountant', 'doctor', 'nurse', 'pathologist', 'radiologist', 'receptionist'];
        if (!in_array($role, $validRoles)) {
            return response()->json(['error' => 'Invalid role specified.'], 400);
        }

        // Fetch users by role
        $users = User::where('role', $role)->get(['id', 'name', 'email', 'role', 'hospitalId', 'created_at']);

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No users found for this role.'], 404);
        }

        return response()->json([
            'message' => "Users with role '$role' retrieved successfully.",
            'users' => $users
        ], 200);
    }

    /**
     * Delete a user by ID (Admin-only action).
     *
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     tags={"Admin"},
     *     summary="Delete a user by their ID",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="User deleted successfully"),
     *     @OA\Response(response=403, description="Unauthorized action"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function deleteUser(Request $request, $id)
    {
        if (!$request->user() || !in_array($request->user()->role, ['admin'])) {
            return response()->json(['error' => 'Unauthorized. Only admins can delete users.'], 403);
        }

        // Find the user by ID
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // Prevent deleting self (optional safety check)
        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'You cannot delete yourself.'], 403);
        }

        // Delete the user
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.'], 200);
    }

    /**
     * Generate and send OTP to the user.
     */
    private function sendOtp($user)
    {
        $otp = rand(1000, 9999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        Mail::raw("Your OTP is $otp", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Your OTP Code');
        });

        return true;
    }
}
