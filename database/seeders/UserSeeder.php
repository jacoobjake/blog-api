<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
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
            $user = User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('Password@1234'),
            ]);
            $user->assignRole(Role::SUPERADMIN->value);
        } elseif (! $existingUser->hasRole(Role::SUPERADMIN->value)) {
            $existingUser->syncRoles(Role::SUPERADMIN->value);
        }
    }
}
