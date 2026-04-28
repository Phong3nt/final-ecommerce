<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the 'user' role exists
        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $users = [
            ['name' => 'John Doe',        'email' => 'john.doe@example.com'],
            ['name' => 'Jane Smith',       'email' => 'jane.smith@example.com'],
            ['name' => 'Alex Johnson',     'email' => 'alex.johnson@example.com'],
            ['name' => 'Sarah Wilson',     'email' => 'sarah.wilson@example.com'],
            ['name' => 'Michael Brown',    'email' => 'michael.brown@example.com'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]
            );

            if (!$user->hasRole('user')) {
                $user->assignRole($role);
            }
        }
    }
}
