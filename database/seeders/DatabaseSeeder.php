<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'User',
            'email' => 'user@user.com',
            'password' => 'password',
            'role' => 'user',
        ]);

        $this->call(PettyCashSeeder::class);
    }
}
