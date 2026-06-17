<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admin extends Actor
{
    protected $fillable = [
        'admin_slug',
        'role_slug',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'profile_image',
        'status',
    ];

    protected $hidden = [
        "deleted_at",
    ];

    protected $casts = [
        'deleted_at'        => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        "profile_image"     => "array",
        "status"            => "string",
    ];

    public function getRouteKeyName(): string
    {
        return "admin_slug";
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_slug', 'role_slug');
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->role_slug) {
            return false;
        }

        $this->loadMissing('role.permissions');

        return $this->role?->permissions->contains('name', $permission) ?? false;
    }

    public function toAuthArray(): array
    {
        $data = $this->toArray();
        $this->loadMissing('role.permissions');

        if ($this->role) {
            $data['role'] = [
                'roleSlug' => $this->role->role_slug,
                'name' => $this->role->name,
                'permissions' => $this->role->permissions->map(fn ($p) => [
                    'name' => $p->name,
                    'label' => $p->label,
                ])->values()->all(),
            ];
        }

        return $data;
    }
}
