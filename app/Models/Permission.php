<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * Get the roles that have this permission (string-based).
     */
    public function roles()
    {
        return $this->hasManyThrough(
            User::class, // Not really "through" User, but we simulate role association
            'permission_role', // Pivot table
            'permission_id', // Foreign key on pivot
            'role', // Local key on User (string column)
            'id', // Local key on Permission
            'role' // Foreign key on pivot (string)
        );
    }

    /**
     * Get the users that have this permission directly.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'permission_user');
    }
}
