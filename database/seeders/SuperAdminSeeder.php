<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates only the initial Super Admin account.
 *
 * This is intentionally separate from AccountSeeder so running it never
 * resets the passwords of existing staff accounts. Change the credentials
 * below (or the password after first login) before deploying anywhere public.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accounts')->updateOrInsert(
            ['email' => 'superadmin@ieti.edu.ph'],
            [
                'username' => 'superadmin',
                'password' => Hash::make('superadmin12345'),
                'role' => 'SuperAdmin',
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'must_reset_password' => false,
                'temporary_password' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
