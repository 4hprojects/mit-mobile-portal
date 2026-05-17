<?php

namespace App\Http\Controllers;

use App\Models\MobileUser;
use App\Models\TemporaryLoginToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class MobileSystemTokenController extends Controller
{
    public function leave(Request $request): JsonResponse
    {
        return $this->issueForSystem($request->user(), 'leave');
    }

    public function medical(): JsonResponse
    {
        return $this->error(
            'medical_placeholder',
            'Medical Management integration is not enabled yet because its auth schema is still pending.',
            501
        );
    }

    private function issueForSystem(MobileUser $user, string $system): JsonResponse
    {
        $access = $user->systemAccess;
        $systemConfig = Config::get("mobile_portal.systems.{$system}");

        if (! $systemConfig || ! ($systemConfig['enabled'] ?? false)) {
            return $this->error('system_disabled', 'This system is not enabled for mobile access.', 403);
        }

        if ($system === 'leave' && (! $access?->can_access_leave || ! $access->leave_user_id)) {
            return $this->error('leave_access_denied', 'Leave Management access is not enabled for this user.', 403);
        }

        $plainToken = Str::random(64);
        $expiresAt = Carbon::now()->addSeconds((int) Config::get('mobile_portal.temporary_token_ttl_seconds', 60));

        TemporaryLoginToken::query()->create([
            'mobile_user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'system_target' => $system,
            'expires_at' => $expiresAt,
        ]);

        $baseUrl = rtrim((string) $systemConfig['url'], '/');

        return response()->json([
            'success' => true,
            'system' => $system,
            'expires_at' => $expiresAt->toISOString(),
            'loginUrl' => "{$baseUrl}/mobile-login?token={$plainToken}",
        ]);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => compact('code', 'message'),
        ], $status);
    }
}
