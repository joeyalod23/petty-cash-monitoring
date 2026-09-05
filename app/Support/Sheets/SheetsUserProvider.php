<?php

namespace App\Support\Sheets;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;

class SheetsUserProvider implements UserProvider
{
    public function __construct(private Hasher $hasher) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        return User::find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $user = User::find($identifier);

        if (!$user || !$user->remember_token) {
            return null;
        }

        return hash_equals((string) $user->remember_token, (string) $token) ? $user : null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->remember_token = $token;
        $user->save();
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials)) {
            return null;
        }

        $query = User::query();

        foreach ($credentials as $key => $value) {
            if (str_contains($key, 'password')) {
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (!isset($credentials['password'])) {
            return false;
        }

        return $this->hasher->check($credentials['password'], (string) $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (!$force && !$this->hasher->needsRehash($user->getAuthPassword())) {
            return;
        }

        $user->password = $this->hasher->make($credentials['password'] ?? '');
        $user->save();
    }
}