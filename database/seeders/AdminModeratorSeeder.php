<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminModeratorSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'photo_credits' => 10
        ]);

        User::updateOrCreate([
            'email' => 'mod@example.com'
        ], [
            'name' => 'Moderator',
            'password' => Hash::make('mod123'),
            'role' => 'moderator',
            'photo_credits' => 10
        ]);
    }
}
