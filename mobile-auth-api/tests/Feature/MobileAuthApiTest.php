<?php

namespace Tests\Feature;

use App\Models\MobileUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('mobile_portal.jwt_secret', 'testing-mobile-jwt-secret');
        Config::set('mobile_portal.integration_secret', 'testing-integration-secret');
        Config::set('mobile_portal.systems.leave.url', 'https://leave-management-mdjw.onrender.com');
        Config::set('mobile_portal.systems.leave.enabled', true);
        Config::set('mobile_portal.systems.medical.enabled', false);
    }

    public function test_mobile_user_can_login_and_request_leave_login_url(): void
    {
        $user = $this->mobileUser();
        $user->systemAccess()->create([
            'leave_user_id' => 25,
            'can_access_leave' => true,
            'can_access_medical' => false,
        ]);

        $login = $this->postJson('/api/mobile/login', [
            'login' => 'admin@leavemgmt.com',
            'password' => 'secret-password',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.access.leave', true)
            ->assertJsonPath('user.access.medical', false);

        $token = $login->json('token');

        $this->withToken($token)
            ->postJson('/api/mobile/token/leave')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('system', 'leave')
            ->assertJson(fn ($json) => $json->whereType('loginUrl', 'string')->etc());
    }

    public function test_leave_temporary_token_can_only_be_verified_once(): void
    {
        $user = $this->mobileUser();
        $user->systemAccess()->create([
            'leave_user_id' => 25,
            'can_access_leave' => true,
            'can_access_medical' => false,
        ]);

        $mobileToken = $this->postJson('/api/mobile/login', [
            'login' => 'admin',
            'password' => 'secret-password',
        ])->json('token');

        $loginUrl = $this->withToken($mobileToken)
            ->postJson('/api/mobile/token/leave')
            ->json('loginUrl');

        parse_str(parse_url($loginUrl, PHP_URL_QUERY), $query);
        $temporaryToken = $query['token'];

        $this->withToken('testing-integration-secret')
            ->postJson('/api/mobile/verify-token', [
                'token' => $temporaryToken,
                'system' => 'leave',
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('system_user.id', 25);

        $this->withToken('testing-integration-secret')
            ->postJson('/api/mobile/verify-token', [
                'token' => $temporaryToken,
                'system' => 'leave',
            ])->assertStatus(409)
            ->assertJsonPath('error.code', 'token_used');
    }

    public function test_medical_token_endpoint_is_placeholder_for_now(): void
    {
        $user = $this->mobileUser();
        $user->systemAccess()->create([
            'leave_user_id' => 25,
            'can_access_leave' => true,
            'can_access_medical' => false,
        ]);

        $mobileToken = $this->postJson('/api/mobile/login', [
            'login' => 'admin@leavemgmt.com',
            'password' => 'secret-password',
        ])->json('token');

        $this->withToken($mobileToken)
            ->postJson('/api/mobile/token/medical')
            ->assertStatus(501)
            ->assertJsonPath('error.code', 'medical_placeholder');
    }

    private function mobileUser(): MobileUser
    {
        return MobileUser::query()->create([
            'employee_id' => null,
            'name' => 'John Admin',
            'email' => 'admin@leavemgmt.com',
            'username' => 'admin',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);
    }
}
