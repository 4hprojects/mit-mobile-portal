<?php

namespace Tests\Feature;

use App\Models\MobileUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncLeaveMobileUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('mobile_portal.systems.leave.url', 'https://leave.test');
        Config::set('mobile_portal.systems.leave.sync_secret', 'sync-secret');
    }

    public function test_it_syncs_leave_users_into_mobile_auth(): void
    {
        Http::fake([
            'https://leave.test/api/mobile/users*' => Http::response([
                'success' => true,
                'data' => [
                    [
                        'leave_user_id' => 1,
                        'name' => 'John Admin',
                        'email' => 'admin@leavemgmt.com',
                        'username' => 'admin',
                        'status' => 'active',
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 100,
                    'total' => 1,
                ],
            ]),
        ]);

        $this->artisan('mobile-users:sync-leave --password=mobile-password')
            ->assertSuccessful();

        $user = MobileUser::query()
            ->with('systemAccess')
            ->where('email', 'admin@leavemgmt.com')
            ->firstOrFail();

        $this->assertSame('John Admin', $user->name);
        $this->assertSame('admin', $user->username);
        $this->assertSame('active', $user->status);
        $this->assertSame(1, $user->systemAccess->leave_user_id);
        $this->assertTrue($user->systemAccess->can_access_leave);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sync-secret'));
    }

    public function test_inactive_leave_users_are_synced_without_leave_access(): void
    {
        Http::fake([
            'https://leave.test/api/mobile/users*' => Http::response([
                'success' => true,
                'data' => [
                    [
                        'leave_user_id' => 2,
                        'name' => 'Archived User',
                        'email' => 'archived@example.test',
                        'username' => 'archived',
                        'status' => 'inactive',
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 100,
                    'total' => 1,
                ],
            ]),
        ]);

        $this->artisan('mobile-users:sync-leave')
            ->assertSuccessful();

        $user = MobileUser::query()
            ->with('systemAccess')
            ->where('email', 'archived@example.test')
            ->firstOrFail();

        $this->assertSame('inactive', $user->status);
        $this->assertFalse($user->systemAccess->can_access_leave);
    }
}
