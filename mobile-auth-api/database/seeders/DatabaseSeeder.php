<?php

namespace Database\Seeders;

use App\Models\MobileUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $mobileUser = MobileUser::query()->firstOrCreate(
            ['email' => 'admin@leavemgmt.com'],
            [
                'employee_id' => null,
                'name' => 'John Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        $mobileUser->systemAccess()->updateOrCreate(
            ['mobile_user_id' => $mobileUser->id],
            [
                'leave_user_id' => 1,
                'medical_user_id' => null,
                'can_access_leave' => true,
                'can_access_medical' => false,
            ]
        );
    }
}
