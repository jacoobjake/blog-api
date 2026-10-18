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

    protected function seedSuperAdmin(): void
    {
        $superadmin = config('seeding.superadmin');

        $existingUser = User::firstWhere('email', $superadmin['email']);

        if (! $existingUser) {
            $user = User::create([
                'name' => $superadmin['name'],
                'email' => $superadmin['email'],
                'password' => Hash::make($superadmin['password']),
            ]);
            $user->assignRole(Role::SUPERADMIN->value);
        } elseif (! $existingUser->hasRole(Role::SUPERADMIN->value)) {
            $existingUser->syncRoles(Role::SUPERADMIN->value);
        }
    }
}
