<?php

namespace Database\Seeders;

use App\Models\Sheet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SheetSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $sheets = [];

        for ($i = 1; $i <= 20; $i++) {
            $sheets[] = [
                'name' => 'Sheet '.$i,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Sheet::insert($sheets);
    }
}
