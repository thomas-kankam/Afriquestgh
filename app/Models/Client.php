<?php

namespace App\Models;

class Client extends Actor
{
    protected $fillable = [
        'client_slug',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'location',
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
        return "client_slug";
    }
}
