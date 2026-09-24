<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['username' => 'admin',  'email' => 'admin@ieti.edu.ph',  'password' => 'admin12345',  'role' => 'Admin'],
            ['username' => 'andrew', 'email' => 'andrew@ieti.edu.ph', 'password' => 'andrew12345', 'role' => 'Registrar'],
        ];

        foreach ($accounts as $account) {
            DB::table('accounts')->updateOrInsert(
                ['email' => $account['email']],
                [
                    'username' => $account['username'],
                    'password' => Hash::make($account['password']),
                    'role' => $account['role'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
