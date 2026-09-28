<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\IngredientCategory;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /** category => [name => default unit] */
    private const DATA = [
        'Vegetables' => [
            'Onion' => 'piece', 'Tomato' => 'piece', 'Potato' => 'piece', 'Carrot' => 'piece',
            'Green Chilli' => 'piece', 'Ginger' => 'g', 'Garlic' => 'g', 'Green Peas' => 'g',
            'Capsicum' => 'piece', 'Curry Leaves' => 'g', 'Coriander Leaves' => 'g', 'Spring Onion' => 'g',
        ],
        'Fruits' => ['Lemon' => 'piece', 'Banana' => 'piece'],
        'Dairy & Eggs' => [
            'Egg' => 'piece', 'Milk' => 'ml', 'Curd' => 'g', 'Paneer' => 'g', 'Butter' => 'g', 'Ghee' => 'ml',
        ],
        'Meat & Seafood' => ['Chicken' => 'g', 'Mutton' => 'g', 'Fish' => 'g'],
        'Grains & Flours' => [
            'Rice' => 'g', 'Basmati Rice' => 'g', 'Wheat Flour' => 'g', 'Rava' => 'g', 'Bread' => 'packet',
        ],
        'Pulses' => ['Toor Dal' => 'g', 'Moong Dal' => 'g', 'Urad Dal' => 'g', 'Chana Dal' => 'g'],
        'Spices' => [
            'Salt' => 'g', 'Turmeric Powder' => 'tsp', 'Red Chilli Powder' => 'tsp', 'Coriander Powder' => 'tsp',
            'Garam Masala' => 'tsp', 'Cumin Seeds' => 'tsp', 'Mustard Seeds' => 'tsp', 'Black Pepper' => 'tsp',
            'Hing' => 'tsp',
        ],
        'Oils & Sauces' => ['Cooking Oil' => 'ml', 'Soy Sauce' => 'tbsp', 'Tomato Ketchup' => 'tbsp'],
        'Nuts & Dry Fruits' => ['Cashew' => 'g', 'Coconut' => 'piece'],
        'Bakery & Others' => ['Sugar' => 'g', 'Tea Powder' => 'g', 'Water' => 'ml'],
    ];

    public function run(): void
    {
        $order = 0;
        foreach (self::DATA as $categoryName => $ingredients) {
            $category = IngredientCategory::updateOrCreate(['name' => $categoryName], ['sort_order' => $order++]);

            foreach ($ingredients as $name => $unit) {
                Ingredient::updateOrCreate(
                    ['normalized_name' => Ingredient::normalize($name)],
                    ['name' => $name, 'ingredient_category_id' => $category->id, 'default_unit' => $unit],
                );
            }
        }
    }
}
