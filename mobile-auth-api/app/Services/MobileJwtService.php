<?php

namespace App\Services;

use App\Models\MobileUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class MobileJwtService
{
    private const ALGORITHM = 'HS256';

    public function issueForUser(MobileUser $user): string
    {
        $now = Carbon::now()->timestamp;
        $ttl = max(1, (int) Config::get('mobile_portal.jwt_ttl_minutes', 1440));

        return $this->encode([
            'iss' => Config::get('app.url'),
            'sub' => (string) $user->getKey(),
            'iat' => $now,
            'exp' => $now + ($ttl * 60),
        ]);
    }

    public function userFromToken(?string $token): ?MobileUser
    {
        if (! $token) {
            return null;
        }

        try {
            $payload = $this->decode($token);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (! $payload || empty($payload['sub'])) {
            return null;
        }

        return MobileUser::query()
            ->with('systemAccess')
            ->whereKey($payload['sub'])
            ->first();
    }

    private function encode(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGORITHM,
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];

        $segments[] = $this->signature($segments[0].'.'.$segments[1]);

        return implode('.', $segments);
    }

    private function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $signature] = $parts;
        $expectedSignature = $this->signature($encodedHeader.'.'.$encodedPayload);

        if (! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $header = $this->jsonDecodeSegment($encodedHeader);
        $payload = $this->jsonDecodeSegment($encodedPayload);

        if (($header['alg'] ?? null) !== self::ALGORITHM) {
            return null;
        }

        if (($payload['exp'] ?? 0) < Carbon::now()->timestamp) {
            return null;
        }

        return $payload;
    }

    private function signature(string $value): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $value, $this->secret(), true));
    }

    private function jsonDecodeSegment(string $segment): array
    {
        $decoded = json_decode($this->base64UrlDecode($segment), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64url JWT segment.');
        }

        return $decoded;
    }

    private function secret(): string
    {
        $secret = (string) Config::get('mobile_portal.jwt_secret');

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);

            return $decoded !== false ? $decoded : $secret;
        }

        return $secret;
    }
}
