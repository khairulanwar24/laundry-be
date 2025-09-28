<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ResponseJson;

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;

        $user = User::create($data);
        $device = $request->input('device_name', 'mobile');
        $token = $user->createToken($device)->plainTextToken;

        return $this->created([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->fail('Email atau password salah', [], 422);
        }
        if (! $user->is_active) {
            return $this->fail('Akun tidak aktif', [], 403);
        }
        $device = $request->input('device_name', 'mobile');
        $token = $user->createToken($device)->plainTextToken;
        activity()->causedBy($user)->log('login');

        return $this->ok([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return $this->ok([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        activity()->causedBy($user)->log('logout');

        return $this->ok(null, 'Logged out');
    }
}
