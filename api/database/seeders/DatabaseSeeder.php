<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Safe to run repeatedly. */
    public function run(): void
    {
        $this->call([IngredientSeeder::class, RecipeSeeder::class, DemoSeeder::class]);
    }
}
