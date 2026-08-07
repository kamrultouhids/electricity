<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the default expense categories.
     */
    public function run(): void
    {
        $categories = ['Line Maintenance', 'Transformer', 'Salaries', 'Other'];

        foreach ($categories as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name], ['status' => 1]);
        }
    }
}
