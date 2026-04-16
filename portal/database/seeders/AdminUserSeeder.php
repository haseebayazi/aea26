<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@alumni-awards.com'],
            [
                'name'      => 'Administrator',
                'email'     => 'admin@alumni-awards.com',
                'password'  => Hash::make('Alumni@2026'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        $this->command->info('Admin user seeded: admin@alumni-awards.com');
        $this->command->warn('  ⚠  Default credentials are set. Change the password immediately after first login.');
    }
}
