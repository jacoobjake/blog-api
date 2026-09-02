<?php

namespace Database\Seeders;

use App\Enums\Role;
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
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);

        $testUser = config('seeding.test_user');

        User::factory()->create([
            'name' => $testUser['name'],
            'email' => $testUser['email'],
        ])->assignRole(Role::AUTHOR->value);
    }
}
