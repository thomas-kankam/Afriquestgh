<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_slug', 'client_slug', 'fullname', 'email', 'phone_number', 'message', 'status', 'type',
    ];

    public function getRouteKeyName(): string
    {
        return 'contact_slug';
    }
}
