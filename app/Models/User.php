<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'hospitalId', 'otp', 'otp_expires_at',
    ];

    protected $hidden = [
        'password', 'otp', 'otp_expires_at',
    ];

    /**
     * Get the permissions assigned directly to the user.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    /**
     * Check if the user has a specific permission (via role or directly).
     */
    public function hasPermission($permissionName)
    {
        // Check direct user permissions
        if ($this->permissions()->where('name', $permissionName)->exists()) {
            return true;
        }

        // Check permissions assigned to the user's role (string-based)
        return DB::table('permission_role')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('permission_role.role', $this->role)
            ->where('permissions.name', $permissionName)
            ->exists();
    }
}
