<?php

namespace App\Http\Controllers;

use App\Models\MobileUser;
use App\Services\MobileJwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function login(Request $request, MobileJwtService $jwtService): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($validated['login']);

        $user = MobileUser::query()
            ->with('systemAccess')
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('username', $login)
                    ->orWhere('employee_id', $login);
            })
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid username or password.'],
            ]);
        }

        if (! $user->isActive()) {
            return $this->error('mobile_user_disabled', 'This mobile account is disabled.', 403);
        }

        return response()->json([
            'success' => true,
            'token' => $jwtService->issueForUser($user),
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(): JsonResponse
    {
        return response()->json([
            'success' => true,
        ]);
    }

    private function userPayload(MobileUser $user): array
    {
        $access = $user->systemAccess;

        return [
            'id' => $user->id,
            'employee_id' => $user->employee_id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'access' => [
                'leave' => (bool) ($access?->can_access_leave),
                'medical' => false,
            ],
            'placeholders' => [
                'medical' => true,
            ],
        ];
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => compact('code', 'message'),
        ], $status);
    }
}
