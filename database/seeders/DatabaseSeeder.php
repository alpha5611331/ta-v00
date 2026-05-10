<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@voxora.local',
            'password' => bcrypt('admin123'),
            'is_admin' => true,
            'is_active' => true,
        ]);

        // Create default regular user
        User::create([
            'name' => 'User',
            'email' => 'user@voxora.local',
            'password' => bcrypt('user123'),
            'is_admin' => false,
            'is_active' => true,
        ]);
    }
}
