<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedSuperAdmin();
    }

    protected function seedSuperAdmin()
    {
        $superadmin_data = [
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
        ];

        $existingUser = User::firstWhere('email', $superadmin_data['email']);

        if (!$existingUser) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('Password@1234'),
            ]);
        }
    }
}
