<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileAuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        return $this->issueToken($request->user(), $request->boolean('remember'));
    }

    public function register(Request $request, RegisteredUserController $registration): JsonResponse
    {
        $registration->store($request);

        return $this->issueToken(Auth::user(), false);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    private function issueToken(User $user, bool $remember): JsonResponse
    {
        $token = $user->createToken('NativePHP Mobile', ['mobile'], now()->addDays($remember ? config('mobile.token_days') : 1));

        return response()->json(['token' => $token->plainTextToken]);
    }
}
