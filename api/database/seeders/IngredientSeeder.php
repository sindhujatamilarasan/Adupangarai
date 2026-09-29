<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\IngredientCategory;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /** category => [name => [default unit, icon]] */
    private const CATEGORY_ICONS = ['Vegetables' => '🥦', 'Fruits' => '🍎', 'Dairy & Eggs' => '🥛', 'Meat & Seafood' => '🍗', 'Grains & Flours' => '🌾', 'Pulses' => '🫘', 'Spices' => '🌶️', 'Oils & Sauces' => '🫗', 'Nuts & Dry Fruits' => '🥜', 'Bakery & Others' => '🍞'];

    private const DATA = [
        'Vegetables' => [
            'Onion' => ['piece', '🧅'], 'Tomato' => ['piece', '🍅'], 'Potato' => ['piece', '🥔'], 'Carrot' => ['piece', '🥕'],
            'Green Chilli' => ['piece', '🌶️'], 'Ginger' => ['g', '🫚'], 'Garlic' => ['g', '🧄'], 'Green Peas' => ['g', '🫛'],
            'Capsicum' => ['piece', '🫑'], 'Curry Leaves' => ['g', '🌿'], 'Coriander Leaves' => ['g', '🌿'], 'Spring Onion' => ['g', '🌱'], 'Spinach' => ['g', '🥬'], 'Cucumber' => ['piece', '🥒'],
        ],
        'Fruits' => ['Lemon' => ['piece', '🍋'], 'Banana' => ['piece', '🍌']],
        'Dairy & Eggs' => [
            'Egg' => ['piece', '🥚'], 'Milk' => ['ml', '🥛'], 'Curd' => ['g', '🥣'], 'Paneer' => ['g', '🧀'], 'Butter' => ['g', '🧈'], 'Ghee' => ['ml', '🫙'],
        ],
        'Meat & Seafood' => ['Chicken' => ['g', '🍗'], 'Mutton' => ['g', '🍖'], 'Fish' => ['g', '🐟']],
        'Grains & Flours' => [
            'Rice' => ['g', '🍚'], 'Basmati Rice' => ['g', '🍚'], 'Wheat Flour' => ['g', '🌾'], 'Rava' => ['g', '🌾'], 'Bread' => ['packet', '🍞'], 'Oats' => ['g', '🥣'], 'Ragi Flour' => ['g', '🌾'],
        ],
        'Pulses' => ['Toor Dal' => ['g', '🫘'], 'Moong Dal' => ['g', '🫘'], 'Urad Dal' => ['g', '🫘'], 'Chana Dal' => ['g', '🫘'], 'Chickpeas' => ['g', '🫘'], 'Green Gram' => ['g', '🫘']],
        'Spices' => [
            'Salt' => ['g', '🧂'], 'Turmeric Powder' => ['tsp', '🟡'], 'Red Chilli Powder' => ['tsp', '🌶️'], 'Coriander Powder' => ['tsp', '🌿'],
            'Garam Masala' => ['tsp', '🫙'], 'Cumin Seeds' => ['tsp', '🟤'], 'Mustard Seeds' => ['tsp', '🟤'], 'Black Pepper' => ['tsp', '⚫'],
            'Hing' => ['tsp', '🫙'],
        ],
        'Oils & Sauces' => ['Cooking Oil' => ['ml', '🫗'], 'Soy Sauce' => ['tbsp', '🍶'], 'Tomato Ketchup' => ['tbsp', '🥫']],
        'Nuts & Dry Fruits' => ['Cashew' => ['g', '🥜'], 'Coconut' => ['piece', '🥥']],
        'Bakery & Others' => ['Sugar' => ['g', '🍬'], 'Tea Powder' => ['g', '🍵'], 'Water' => ['ml', '💧']],
    ];

    /** Tamil names shown when the app is in Tamil. */
    private const TAMIL = [
        'Onion' => 'வெங்காயம்',
        'Tomato' => 'தக்காளி',
        'Potato' => 'உருளைக்கிழங்கு',
        'Carrot' => 'கேரட்',
        'Green Chilli' => 'பச்சை மிளகாய்',
        'Ginger' => 'இஞ்சி',
        'Garlic' => 'பூண்டு',
        'Green Peas' => 'பச்சைப் பட்டாணி',
        'Capsicum' => 'குடைமிளகாய்',
        'Curry Leaves' => 'கறிவேப்பிலை',
        'Coriander Leaves' => 'கொத்தமல்லி இலை',
        'Spring Onion' => 'வெங்காயத்தாள்',
        'Lemon' => 'எலுமிச்சை',
        'Banana' => 'வாழைப்பழம்',
        'Egg' => 'முட்டை',
        'Milk' => 'பால்',
        'Curd' => 'தயிர்',
        'Paneer' => 'பன்னீர்',
        'Butter' => 'வெண்ணெய்',
        'Ghee' => 'நெய்',
        'Chicken' => 'கோழிக்கறி',
        'Mutton' => 'ஆட்டுக்கறி',
        'Fish' => 'மீன்',
        'Rice' => 'அரிசி',
        'Basmati Rice' => 'பாசுமதி அரிசி',
        'Wheat Flour' => 'கோதுமை மாவு',
        'Rava' => 'ரவை',
        'Bread' => 'ரொட்டி',
        'Toor Dal' => 'துவரம் பருப்பு',
        'Moong Dal' => 'பாசிப் பருப்பு',
        'Urad Dal' => 'உளுத்தம் பருப்பு',
        'Chana Dal' => 'கடலைப் பருப்பு',
        'Salt' => 'உப்பு',
        'Turmeric Powder' => 'மஞ்சள் தூள்',
        'Red Chilli Powder' => 'மிளகாய்த் தூள்',
        'Coriander Powder' => 'மல்லித் தூள்',
        'Garam Masala' => 'கரம் மசாலா',
        'Cumin Seeds' => 'சீரகம்',
        'Mustard Seeds' => 'கடுகு',
        'Black Pepper' => 'மிளகு',
        'Hing' => 'பெருங்காயம்',
        'Cooking Oil' => 'சமையல் எண்ணெய்',
        'Soy Sauce' => 'சோயா சாஸ்',
        'Tomato Ketchup' => 'தக்காளி கெட்சப்',
        'Cashew' => 'முந்திரி',
        'Coconut' => 'தேங்காய்',
        'Sugar' => 'சர்க்கரை',
        'Tea Powder' => 'டீத் தூள்',
        'Water' => 'தண்ணீர்',
        'Spinach' => 'கீரை',
        'Cucumber' => 'வெள்ளரிக்காய்',
        'Oats' => 'ஓட்ஸ்',
        'Ragi Flour' => 'கேழ்வரகு மாவு',
        'Chickpeas' => 'கொண்டைக்கடலை',
        'Green Gram' => 'பச்சைப் பயறு',
    ];

    private const CATEGORY_TAMIL = [
        'Vegetables' => 'காய்கறிகள்',
        'Fruits' => 'பழங்கள்',
        'Dairy & Eggs' => 'பால் & முட்டை',
        'Meat & Seafood' => 'இறைச்சி & கடல் உணவு',
        'Grains & Flours' => 'தானியங்கள் & மாவு',
        'Pulses' => 'பருப்பு வகைகள்',
        'Spices' => 'மசாலாப் பொருட்கள்',
        'Oils & Sauces' => 'எண்ணெய் & சாஸ்',
        'Nuts & Dry Fruits' => 'கொட்டைகள் & உலர் பழங்கள்',
        'Bakery & Others' => 'பேக்கரி & மற்றவை',
    ];

    public function run(): void
    {
        $order = 0;
        foreach (self::DATA as $categoryName => $ingredients) {
            $category = IngredientCategory::updateOrCreate(
                ['name' => $categoryName],
                ['sort_order' => $order++, 'icon' => self::CATEGORY_ICONS[$categoryName], 'name_ta' => self::CATEGORY_TAMIL[$categoryName]],
            );

            foreach ($ingredients as $name => [$unit, $icon]) {
                Ingredient::updateOrCreate(
                    ['normalized_name' => Ingredient::normalize($name)],
                    ['name' => $name, 'name_ta' => self::TAMIL[$name], 'ingredient_category_id' => $category->id, 'default_unit' => $unit, 'icon' => $icon],
                );
            }
        }
    }
}
