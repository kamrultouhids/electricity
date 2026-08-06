<?php

namespace Database\Seeders;

use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::insert([
            [
                'name' => 'Super Admin',
                'user_type' => UserTypeEnum::Admin->value,
                'email' => 'superadmin@gmail.com',
                'password' => Hash::make('Secret@123'),
                'created_at' => now(),
                'updated_at' => now(),
                'email_verified_at' => now()
            ]
        ]);

        $this->call([
            SheetSeeder::class,
        ]);
    }
}
