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
        'Green Peas' => 'பட்டாணி',
        'Capsicum' => 'குடைமிளகாய்',
        'Curry Leaves' => 'கறிவேப்பிலை',
        'Coriander Leaves' => 'கொத்தமல்லி',
        'Spring Onion' => 'ஸ்பிரிங் ஆனியன்',
        'Lemon' => 'எலுமிச்சை',
        'Banana' => 'வாழைப்பழம்',
        'Egg' => 'முட்டை',
        'Milk' => 'பால்',
        'Curd' => 'தயிர்',
        'Paneer' => 'பன்னீர்',
        'Butter' => 'வெண்ணெய்',
        'Ghee' => 'நெய்',
        'Chicken' => 'சிக்கன்',
        'Mutton' => 'மட்டன்',
        'Fish' => 'மீன்',
        'Rice' => 'அரிசி',
        'Basmati Rice' => 'பாஸ்மதி அரிசி',
        'Wheat Flour' => 'கோதுமை மாவு',
        'Rava' => 'ரவா',
        'Bread' => 'பிரெட்',
        'Toor Dal' => 'துவரம்பருப்பு',
        'Moong Dal' => 'பாசிப்பருப்பு',
        'Urad Dal' => 'உளுத்தம்பருப்பு',
        'Chana Dal' => 'கடலைப்பருப்பு',
        'Salt' => 'உப்பு',
        'Turmeric Powder' => 'மஞ்சள்தூள்',
        'Red Chilli Powder' => 'மிளகாய்த்தூள்',
        'Coriander Powder' => 'மல்லித்தூள்',
        'Garam Masala' => 'கரம் மசாலா',
        'Cumin Seeds' => 'சீரகம்',
        'Mustard Seeds' => 'கடுகு',
        'Black Pepper' => 'மிளகு',
        'Hing' => 'பெருங்காயம்',
        'Cooking Oil' => 'எண்ணெய்',
        'Soy Sauce' => 'சோயா சாஸ்',
        'Tomato Ketchup' => 'டொமேட்டோ கெட்சப்',
        'Cashew' => 'முந்திரி',
        'Coconut' => 'தேங்காய்',
        'Sugar' => 'சர்க்கரை',
        'Tea Powder' => 'டீத்தூள்',
        'Water' => 'தண்ணீர்',
        'Spinach' => 'கீரை',
        'Cucumber' => 'வெள்ளரிக்காய்',
        'Oats' => 'ஓட்ஸ்',
        'Ragi Flour' => 'ராகி மாவு',
        'Chickpeas' => 'கொண்டைக்கடலை',
        'Green Gram' => 'பச்சை பயறு',
    ];

    private const CATEGORY_TAMIL = [
        'Vegetables' => 'காய்கறி',
        'Fruits' => 'பழம்',
        'Dairy & Eggs' => 'பால் & முட்டை',
        'Meat & Seafood' => 'கறி & மீன்',
        'Grains & Flours' => 'அரிசி & மாவு',
        'Pulses' => 'பருப்பு',
        'Spices' => 'மசாலா',
        'Oils & Sauces' => 'எண்ணெய் & சாஸ்',
        'Nuts & Dry Fruits' => 'நட்ஸ் & ட்ரை ஃப்ரூட்ஸ்',
        'Bakery & Others' => 'பேக்கரி & மத்தது',
    ];

    /**
     * Usual shop pack per ingredient: [quantity in the ingredient's own unit, what the shop calls it].
     * Spices are kept in teaspoons, so a 100 g packet of turmeric is about 33 tsp (≈3 g per tsp).
     * Loose vegetables bought by the piece have no pack: you buy exactly what you need.
     */
    public const PACKS = [
        'Sugar' => [1000, '1 kg'], 'Tea Powder' => [250, '250 g'],
        'Butter' => [100, '100 g'], 'Curd' => [400, '400 g'], 'Egg' => [6, '6 eggs'], 'Ghee' => [200, '200 ml'],
        'Milk' => [500, '500 ml'], 'Paneer' => [200, '200 g'], 'Banana' => [6, '6 bananas'],
        'Basmati Rice' => [1000, '1 kg'], 'Bread' => [1, '1 packet'], 'Oats' => [500, '500 g'], 'Ragi Flour' => [500, '500 g'],
        'Rava' => [500, '500 g'], 'Rice' => [1000, '1 kg'], 'Wheat Flour' => [1000, '1 kg'],
        'Chicken' => [500, '½ kg'], 'Fish' => [500, '½ kg'], 'Mutton' => [500, '½ kg'],
        'Cashew' => [100, '100 g'], 'Cooking Oil' => [1000, '1 L'], 'Soy Sauce' => [13, '200 ml bottle'], 'Tomato Ketchup' => [33, '500 g bottle'],
        'Chana Dal' => [500, '500 g'], 'Chickpeas' => [500, '500 g'], 'Green Gram' => [500, '500 g'], 'Moong Dal' => [500, '500 g'],
        'Toor Dal' => [500, '500 g'], 'Urad Dal' => [500, '500 g'],
        'Black Pepper' => [20, '50 g'], 'Coriander Powder' => [50, '100 g'], 'Cumin Seeds' => [48, '100 g'], 'Garam Masala' => [20, '50 g'],
        'Hing' => [20, '50 g'], 'Mustard Seeds' => [28, '100 g'], 'Red Chilli Powder' => [40, '100 g'], 'Salt' => [1000, '1 kg'],
        'Turmeric Powder' => [33, '100 g'],
        'Coriander Leaves' => [100, '1 bunch'], 'Curry Leaves' => [50, '1 bunch'], 'Garlic' => [100, '100 g'], 'Ginger' => [100, '100 g'],
        'Green Chilli' => [10, '100 g'], 'Green Peas' => [250, '250 g'], 'Spinach' => [250, '1 bunch'], 'Spring Onion' => [100, '1 bunch'],
        'Onion' => [4, '½ kg'], 'Potato' => [4, '½ kg'], 'Tomato' => [4, '½ kg'], 'Carrot' => [4, '½ kg'], 'Capsicum' => [2, '250 g'],
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
                    ['name' => $name, 'name_ta' => self::TAMIL[$name], 'ingredient_category_id' => $category->id, 'default_unit' => $unit, 'icon' => $icon,
                        'pack_quantity' => self::PACKS[$name][0] ?? null, 'pack_label' => self::PACKS[$name][1] ?? null],
                );
            }
        }
    }
}
