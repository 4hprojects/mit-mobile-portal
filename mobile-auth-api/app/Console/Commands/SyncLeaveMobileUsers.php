<?php

namespace App\Console\Commands;

use App\Models\MobileUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncLeaveMobileUsers extends Command
{
    protected $signature = 'mobile-users:sync-leave
        {--password= : Default mobile password for newly created users}
        {--per-page=100 : Number of Leave users to request per page}
        {--dry-run : Show what would change without writing to the database}';

    protected $description = 'Sync Leave Management users into Mobile Auth using their Leave email addresses.';

    public function handle(): int
    {
        $baseUrl = rtrim((string) config('mobile_portal.systems.leave.url'), '/');
        $secret = (string) config('mobile_portal.systems.leave.sync_secret');
        $defaultPassword = (string) ($this->option('password') ?: env('DEFAULT_MOBILE_USER_PASSWORD', 'password'));
        $perPage = max(1, min((int) $this->option('per-page'), 500));
        $dryRun = (bool) $this->option('dry-run');

        if ($baseUrl === '' || $secret === '') {
            $this->error('LEAVE_APP_URL and LEAVE_APP_SYNC_SECRET must be configured.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $page = 1;
        $lastPage = 1;

        do {
            $response = Http::acceptJson()
                ->withToken($secret)
                ->timeout(20)
                ->get("{$baseUrl}/api/mobile/users", [
                    'page' => $page,
                    'per_page' => $perPage,
                ]);

            if (! $response->successful() || ! $response->json('success')) {
                $this->error("Leave user sync failed on page {$page}: ".$response->body());

                return self::FAILURE;
            }

            foreach ($response->json('data', []) as $leaveUser) {
                if (empty($leaveUser['email']) || empty($leaveUser['leave_user_id'])) {
                    continue;
                }

                $existing = MobileUser::query()
                    ->where('email', $leaveUser['email'])
                    ->first();

                $attributes = [
                    'employee_id' => null,
                    'name' => $leaveUser['name'] ?: $leaveUser['email'],
                    'username' => $this->uniqueUsername($leaveUser['username'] ?: Str::before($leaveUser['email'], '@'), $existing?->id),
                    'status' => $leaveUser['status'] === 'active' ? 'active' : 'inactive',
                ];

                if ($dryRun) {
                    $this->line(($existing ? 'Update' : 'Create')." {$leaveUser['email']} -> Leave {$leaveUser['leave_user_id']}");
                    $existing ? $updated++ : $created++;
                    continue;
                }

                if ($existing) {
                    $existing->update($attributes);
                    $mobileUser = $existing;
                    $updated++;
                } else {
                    $mobileUser = MobileUser::query()->create([
                        ...$attributes,
                        'email' => $leaveUser['email'],
                        'password' => Hash::make($defaultPassword),
                    ]);
                    $created++;
                }

                $mobileUser->systemAccess()->updateOrCreate(
                    ['mobile_user_id' => $mobileUser->id],
                    [
                        'leave_user_id' => $leaveUser['leave_user_id'],
                        'can_access_leave' => $leaveUser['status'] === 'active',
                    ]
                );
            }

            $lastPage = (int) $response->json('meta.last_page', 1);
            $page++;
        } while ($page <= $lastPage);

        $this->info("Leave user sync complete. Created: {$created}. Updated: {$updated}.");

        return self::SUCCESS;
    }

    private function uniqueUsername(string $baseUsername, ?int $existingUserId = null): string
    {
        $baseUsername = Str::slug($baseUsername, '.') ?: 'mobile.user';
        $username = $baseUsername;
        $suffix = 2;

        while (
            MobileUser::query()
                ->where('username', $username)
                ->when($existingUserId, fn ($query) => $query->whereKeyNot($existingUserId))
                ->exists()
        ) {
            $username = "{$baseUsername}.{$suffix}";
            $suffix++;
        }

        return $username;
    }
}
