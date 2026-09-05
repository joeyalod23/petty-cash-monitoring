<?php

namespace App\Models;

use App\Support\Sheets\SheetModel;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;

class User extends SheetModel implements Authenticatable
{
    use AuthenticatableTrait;

    protected array $fillable = ['name', 'email', 'password', 'role'];

    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return ($this->role ?? 'user') === 'admin';
    }
}