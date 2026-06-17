<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['role_slug', 'name'];

    public function getRouteKeyName(): string
    {
        return 'role_slug';
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_slug',
            'permission_name',
            'role_slug',
            'name'
        );
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class, 'role_slug', 'role_slug');
    }
}
