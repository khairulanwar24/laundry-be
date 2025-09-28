<?php

namespace App\Services\Api\V1;

use App\Models\User;
use Exception;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Find existing user by email or phone, or create new user if not found.
     *
     * @throws Exception
     */
    public function findOrCreateByEmailOrPhone(?string $email, ?string $phone, ?string $name = null): User
    {
        if (! $email && ! $phone) {
            throw new Exception('Either email or phone must be provided');
        }

        // Try to find existing user by email or phone
        $user = User::query()
            ->where(function ($query) use ($email, $phone) {
                if ($email) {
                    $query->where('email', $email);
                }
                if ($phone) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->first();

        // Return existing user if found
        if ($user) {
            return $user;
        }

        // Create new user if not found
        $userData = [
            'name' => $name ?? 'User '.Str::random(6),
            'email' => $email,
            'phone' => $phone,
            'password' => bcrypt(Str::random(16)),
            'is_active' => true,
        ];

        $user = User::create($userData);

        if (! $user) {
            throw new Exception('Failed to create user');
        }

        return $user;
    }
}
