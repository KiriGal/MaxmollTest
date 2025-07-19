<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, UserService $userService)
    {
        $request->validated();
        $userService->register($request);
        return response()->json([
            'message' => 'User created'
        ], 201);
    }
    public function login(LoginRequest $request, UserService $userService)
    {
        $request->validated();
        $token = $userService->login($request);
        return response()->json([
            'token' => $token
        ]);
    }
    public function logout(Request $request, UserService $userService)
    {
        $userService->logout($request);
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request){
        return response()->json($request->user());
    }
}
