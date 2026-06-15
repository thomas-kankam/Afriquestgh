<?php

namespace App\Models;

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
}
