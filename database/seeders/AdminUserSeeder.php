<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Crea utente admin se non esiste
        $admin = User::firstOrCreate(
            ['email' => 'admin@goolliver.com'],
            [
                'name' => 'Admin Goolliver',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'is_active' => true
            ]
        );

        // Crea un moderatore di test
        $moderator = User::firstOrCreate(
            ['email' => 'mod@goolliver.com'],
            [
                'name' => 'Moderatore Test',
                'password' => Hash::make('mod123'),
                'email_verified_at' => now(),
                'role' => 'moderator',
                'is_active' => true
            ]
        );

        $this->command->info('Utenti admin e moderatore creati:');
        $this->command->info('Admin: admin@goolliver.com / admin123');
        $this->command->info('Moderator: mod@goolliver.com / mod123');
    }
}
