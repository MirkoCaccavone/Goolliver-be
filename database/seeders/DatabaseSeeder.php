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
        // User::factory(10)->create();


        // Admin profile
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@goolliver.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Moderator profile
        User::factory()->create([
            'name' => 'Moderator',
            'email' => 'mod@goolliver.com',
            'password' => bcrypt('mod123'),
            'role' => 'moderator',
            'is_active' => true,
        ]);

        // Seed contests
        $this->call([
            ContestSeeder::class,
        ]);
    }
}
