<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([IngredientSeeder::class, RecipeSeeder::class]);

        if (! User::where('email', 'demo@adupangarai.test')->exists()) {
            User::create([
                'household_id' => Household::create(['name' => 'Demo Kitchen'])->id,
                'name' => 'Demo',
                'email' => 'demo@adupangarai.test',
                'password' => 'password',
            ]);
        }
    }
}
