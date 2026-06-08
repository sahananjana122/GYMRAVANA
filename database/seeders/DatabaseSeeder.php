<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create the 4 roles
        Role::firstOrCreate(['name' => 'member']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'master']);
        Role::firstOrCreate(['name' => 'trainer']);

        // Create a test admin account
        $admin = User::firstOrCreate(
            ['email' => 'admin@gymravana.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
            ]
        );
        $admin->assignRole('admin');

        // Create a test member account
        $member = User::firstOrCreate(
            ['email' => 'member@gymravana.com'],
            [
                'name' => 'Test Member',
                'password' => Hash::make('password123'),
            ]
        );
        $member->assignRole('member');
    }
}