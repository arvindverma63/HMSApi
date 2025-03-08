<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    /**
     * Create a new permission.
     *
     * @OA\Post(
     *     path="/api/admin/permissions",
     *     tags={"Admin"},
     *     summary="Create a new permission",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="edit_users"),
     *             @OA\Property(property="description", type="string", example="Permission to edit user details", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Permission created successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=403, description="Unauthorized action")
     * )
     */
    public function createPermission(Request $request)
    {
        if (!$request->user() || !$request->user()->hasPermission('manage_permissions')) {
            return response()->json(['error' => 'Unauthorized. You lack permission to manage permissions.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
            'description' => 'nullable|string|max:255',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Permission created successfully.',
            'permission' => $permission
        ], 201);
    }

    /**
     * Get all permissions.
     *
     * @OA\Get(
     *     path="/api/admin/permissions",
     *     tags={"Admin"},
     *     summary="Get all permissions",
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="List of all permissions"),
     *     @OA\Response(response=403, description="Unauthorized action"),
     *     @OA\Response(response=404, description="No permissions found")
     * )
     */
    public function getPermissions(Request $request)
    {
        if (!$request->user() || !$request->user()->hasPermission('manage_permissions')) {
            return response()->json(['error' => 'Unauthorized. You lack permission to manage permissions.'], 403);
        }

        $permissions = Permission::all(['id', 'name', 'description', 'created_at']);

        if ($permissions->isEmpty()) {
            return response()->json(['message' => 'No permissions found.'], 404);
        }

        return response()->json([
            'message' => 'Permissions retrieved successfully.',
            'permissions' => $permissions
        ], 200);
    }

    /**
     * Assign a permission to a role.
     *
     * @OA\Post(
     *     path="/api/admin/permissions/assign",
     *     tags={"Admin"},
     *     summary="Assign a permission to a role",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permission_id", "role"},
     *             @OA\Property(property="permission_id", type="integer", example=1),
     *             @OA\Property(property="role", type="string", example="doctor")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Permission assigned to role successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=403, description="Unauthorized action"),
     *     @OA\Response(response=404, description="Permission not found")
     * )
     */
    public function assignPermissionToRole(Request $request)
    {
        if (!$request->user() || !$request->user()->hasPermission('manage_permissions')) {
            return response()->json(['error' => 'Unauthorized. You lack permission to manage permissions.'], 403);
        }

        $request->validate([
            'permission_id' => 'required|exists:permissions,id',
            'role' => 'required|string|in:admin,accountant,doctor,nurse,pathologist,radiologist,receptionist',
        ]);

        $permission = Permission::find($request->permission_id);
        if (!$permission) {
            return response()->json(['error' => 'Permission not found.'], 404);
        }

        // Check if the permission is already assigned to the role
        $exists = DB::table('permission_role')
            ->where('permission_id', $request->permission_id)
            ->where('role', $request->role)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Permission is already assigned to this role.'], 400);
        }

        // Assign the permission to the role
        DB::table('permission_role')->insert([
            'permission_id' => $request->permission_id,
            'role' => $request->role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => "Permission '{$permission->name}' assigned to role '{$request->role}' successfully."
        ], 200);
    }

    /**
     * Revoke a permission from a role.
     *
     * @OA\Delete(
     *     path="/api/admin/permissions/revoke",
     *     tags={"Admin"},
     *     summary="Revoke a permission from a role",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permission_id", "role"},
     *             @OA\Property(property="permission_id", type="integer", example=1),
     *             @OA\Property(property="role", type="string", example="doctor")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Permission revoked from role successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=403, description="Unauthorized action"),
     *     @OA\Response(response=404, description="Permission or assignment not found")
     * )
     */
    public function revokePermissionFromRole(Request $request)
    {
        if (!$request->user() || !$request->user()->hasPermission('manage_permissions')) {
            return response()->json(['error' => 'Unauthorized. You lack permission to manage permissions.'], 403);
        }

        $request->validate([
            'permission_id' => 'required|exists:permissions,id',
            'role' => 'required|string|in:admin,accountant,doctor,nurse,pathologist,radiologist,receptionist',
        ]);

        $permission = Permission::find($request->permission_id);
        if (!$permission) {
            return response()->json(['error' => 'Permission not found.'], 404);
        }

        // Check if the permission is assigned to the role
        $deleted = DB::table('permission_role')
            ->where('permission_id', $request->permission_id)
            ->where('role', $request->role)
            ->delete();

        if (!$deleted) {
            return response()->json(['error' => 'Permission not assigned to this role.'], 404);
        }

        return response()->json([
            'message' => "Permission '{$permission->name}' revoked from role '{$request->role}' successfully."
        ], 200);
    }
}
