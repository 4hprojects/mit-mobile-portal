<?php

namespace App\Http\Controllers;

use App\Models\TemporaryLoginToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TemporaryTokenVerificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'system' => ['required', 'string', Rule::in(['leave', 'medical'])],
        ]);

        if ($validated['system'] === 'medical') {
            return $this->error(
                'medical_placeholder',
                'Medical Management integration is not enabled yet because its auth schema is still pending.',
                501
            );
        }

        return DB::transaction(function () use ($validated) {
            $temporaryToken = TemporaryLoginToken::query()
                ->where('token_hash', hash('sha256', $validated['token']))
                ->lockForUpdate()
                ->first();

            if (! $temporaryToken) {
                return $this->error('invalid_token', 'Temporary login token is invalid.', 401);
            }

            if ($temporaryToken->system_target !== $validated['system']) {
                return $this->error('wrong_system', 'Temporary login token target does not match this system.', 403);
            }

            if ($temporaryToken->used_at) {
                return $this->error('token_used', 'Temporary login token has already been used.', 409);
            }

            if ($temporaryToken->expires_at->isPast()) {
                return $this->error('token_expired', 'Temporary login token has expired.', 401);
            }

            $user = $temporaryToken->mobileUser()->with('systemAccess')->first();

            if (! $user || ! $user->isActive()) {
                return $this->error('mobile_user_disabled', 'This mobile account is disabled.', 403);
            }

            $access = $user->systemAccess;

            if (! $access?->can_access_leave || ! $access->leave_user_id) {
                return $this->error('leave_access_denied', 'Leave Management access is not enabled for this user.', 403);
            }

            $temporaryToken->update([
                'used_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => true,
                'system' => 'leave',
                'mobile_user' => [
                    'id' => $user->id,
                    'employee_id' => $user->employee_id,
                    'name' => $user->name,
                ],
                'system_user' => [
                    'id' => $access->leave_user_id,
                ],
            ]);
        });
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => compact('code', 'message'),
        ], $status);
    }
}
