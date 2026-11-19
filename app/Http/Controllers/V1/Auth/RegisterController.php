<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->only(['name', 'email', 'password']));

        return response()->json([
            'data' => [
                'user' => $user->only(['id', 'name', 'email']),
                'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
            ],
        ], Response::HTTP_CREATED);
    }
}
