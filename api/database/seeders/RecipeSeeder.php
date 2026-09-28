<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Built-in recipes. Ingredient rows: [name, quantity, unit, optional?].
     * Units must be compatible with the ingredient's default unit.
     */
    private const RECIPES = [
        [
            'name' => 'Omelette', 'meal_type' => 'breakfast', 'cuisine' => 'Indian', 'servings' => 1, 'prep_time' => 5, 'cook_time' => 5, 'is_veg' => false,
            'description' => 'Fluffy masala omelette with onion and green chilli.',
            'ingredients' => [['Egg', 2, 'piece'], ['Onion', 0.5, 'piece'], ['Green Chilli', 1, 'piece'], ['Salt', 2, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Black Pepper', 0.25, 'tsp', true], ['Coriander Leaves', 5, 'g', true]],
            'steps' => ['Finely chop onion, chilli and coriander.', 'Beat eggs with salt, pepper and the chopped vegetables.', 'Heat oil in a pan, pour in the eggs and spread evenly.', 'Cook until set, flip and cook 30 seconds more.'],
        ],
        [
            'name' => 'Egg Fried Rice', 'meal_type' => 'lunch', 'cuisine' => 'Indo-Chinese', 'servings' => 2, 'prep_time' => 10, 'cook_time' => 15, 'is_veg' => false,
            'description' => 'Quick street-style fried rice with scrambled egg.',
            'ingredients' => [['Basmati Rice', 200, 'g'], ['Egg', 3, 'piece'], ['Onion', 1, 'piece'], ['Carrot', 1, 'piece'], ['Garlic', 10, 'g'], ['Soy Sauce', 1, 'tbsp'], ['Black Pepper', 0.5, 'tsp'], ['Salt', 4, 'g'], ['Cooking Oil', 2, 'tbsp'], ['Capsicum', 1, 'piece', true], ['Spring Onion', 20, 'g', true]],
            'steps' => ['Cook the rice, spread it out and let it cool.', 'Scramble the eggs in a little oil and set aside.', 'Stir-fry garlic, onion, carrot and capsicum on high heat.', 'Add rice, soy sauce, pepper, salt and the eggs; toss well.', 'Finish with spring onion.'],
        ],
        [
            'name' => 'Chicken Fried Rice', 'meal_type' => 'dinner', 'cuisine' => 'Indo-Chinese', 'servings' => 2, 'prep_time' => 15, 'cook_time' => 20, 'is_veg' => false,
            'description' => 'Fried rice loaded with chicken, egg and vegetables.',
            'ingredients' => [['Basmati Rice', 200, 'g'], ['Chicken', 250, 'g'], ['Egg', 2, 'piece'], ['Onion', 1, 'piece'], ['Carrot', 1, 'piece'], ['Garlic', 10, 'g'], ['Soy Sauce', 2, 'tbsp'], ['Black Pepper', 0.5, 'tsp'], ['Salt', 4, 'g'], ['Cooking Oil', 2, 'tbsp'], ['Capsicum', 1, 'piece', true], ['Spring Onion', 20, 'g', true]],
            'steps' => ['Cook and cool the rice.', 'Cut chicken into small pieces and stir-fry until cooked.', 'Scramble the eggs and set aside.', 'Stir-fry garlic and vegetables, add chicken, rice and sauces.', 'Toss in the egg and spring onion and serve hot.'],
        ],
        [
            'name' => 'Chicken Curry', 'meal_type' => 'dinner', 'cuisine' => 'South Indian', 'servings' => 4, 'prep_time' => 15, 'cook_time' => 35, 'is_veg' => false,
            'description' => 'Home-style spicy chicken curry with onion-tomato masala.',
            'ingredients' => [['Chicken', 500, 'g'], ['Onion', 2, 'piece'], ['Tomato', 2, 'piece'], ['Ginger', 15, 'g'], ['Garlic', 15, 'g'], ['Green Chilli', 2, 'piece'], ['Turmeric Powder', 0.5, 'tsp'], ['Red Chilli Powder', 2, 'tsp'], ['Coriander Powder', 2, 'tsp'], ['Garam Masala', 1, 'tsp'], ['Salt', 8, 'g'], ['Cooking Oil', 3, 'tbsp'], ['Curd', 50, 'g', true], ['Curry Leaves', 5, 'g', true], ['Coriander Leaves', 10, 'g', true]],
            'steps' => ['Marinate chicken with turmeric, salt and curd for 15 minutes.', 'Sauté onions in oil until golden, add ginger-garlic and chilli.', 'Add tomatoes and the spice powders; cook until oil separates.', 'Add chicken and a cup of water; cover and cook 25 minutes.', 'Finish with garam masala and coriander leaves.'],
        ],
        [
            'name' => 'Tomato Rice', 'meal_type' => 'lunch', 'cuisine' => 'South Indian', 'servings' => 3, 'prep_time' => 10, 'cook_time' => 20, 'is_veg' => true,
            'description' => 'Tangy one-pot tomato rice, perfect for lunch boxes.',
            'ingredients' => [['Rice', 300, 'g'], ['Tomato', 4, 'piece'], ['Onion', 1, 'piece'], ['Green Chilli', 2, 'piece'], ['Ginger', 10, 'g'], ['Garlic', 10, 'g'], ['Mustard Seeds', 1, 'tsp'], ['Turmeric Powder', 0.5, 'tsp'], ['Red Chilli Powder', 1, 'tsp'], ['Salt', 6, 'g'], ['Cooking Oil', 3, 'tbsp'], ['Curry Leaves', 5, 'g', true], ['Coriander Leaves', 10, 'g', true]],
            'steps' => ['Cook the rice and keep aside.', 'Splutter mustard seeds in oil, add curry leaves, onion, chilli, ginger and garlic.', 'Add chopped tomatoes and spices; cook to a thick masala.', 'Mix in the rice gently and garnish with coriander.'],
        ],
        [
            'name' => 'Curd Rice', 'meal_type' => 'lunch', 'cuisine' => 'South Indian', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 20, 'is_veg' => true,
            'description' => 'Cooling thayir sadam with a mustard-curry leaf tempering.',
            'ingredients' => [['Rice', 150, 'g'], ['Curd', 300, 'g'], ['Green Chilli', 1, 'piece'], ['Ginger', 5, 'g'], ['Mustard Seeds', 0.5, 'tsp'], ['Urad Dal', 5, 'g'], ['Salt', 3, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Milk', 100, 'ml', true], ['Curry Leaves', 3, 'g', true], ['Hing', 0.25, 'tsp', true], ['Coriander Leaves', 5, 'g', true]],
            'steps' => ['Cook rice until soft and mash lightly; let it cool.', 'Mix in curd, milk and salt.', 'Temper mustard, urad dal, chilli, ginger, curry leaves and hing in oil.', 'Pour the tempering over the rice and mix.'],
        ],
        [
            'name' => 'Dal', 'meal_type' => 'lunch', 'cuisine' => 'Indian', 'servings' => 4, 'prep_time' => 10, 'cook_time' => 30, 'is_veg' => true,
            'description' => 'Simple toor dal with a ghee-cumin tadka.',
            'ingredients' => [['Toor Dal', 200, 'g'], ['Tomato', 2, 'piece'], ['Onion', 1, 'piece'], ['Green Chilli', 2, 'piece'], ['Garlic', 10, 'g'], ['Turmeric Powder', 0.5, 'tsp'], ['Cumin Seeds', 1, 'tsp'], ['Ghee', 2, 'tbsp'], ['Salt', 6, 'g'], ['Hing', 0.25, 'tsp', true], ['Coriander Leaves', 10, 'g', true]],
            'steps' => ['Pressure cook dal with turmeric and tomato for 3 whistles.', 'Heat ghee, add cumin, garlic, hing, onion and chilli.', 'Pour the tadka into the mashed dal, add salt and simmer 5 minutes.', 'Garnish with coriander.'],
        ],
        [
            'name' => 'Chapati', 'meal_type' => 'dinner', 'cuisine' => 'North Indian', 'servings' => 4, 'prep_time' => 15, 'cook_time' => 20, 'is_veg' => true,
            'description' => 'Soft whole-wheat chapatis (about 8).',
            'ingredients' => [['Wheat Flour', 300, 'g'], ['Salt', 3, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Ghee', 2, 'tbsp', true]],
            'steps' => ['Knead flour, salt and oil with about 180 ml water into a soft dough.', 'Rest the dough for 15 minutes and divide into 8 balls.', 'Roll each thin and cook on a hot tawa on both sides.', 'Brush with ghee and keep covered.'],
        ],
        [
            'name' => 'Upma', 'meal_type' => 'breakfast', 'cuisine' => 'South Indian', 'servings' => 3, 'prep_time' => 5, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Classic rava upma with onion and cashews.',
            'ingredients' => [['Rava', 200, 'g'], ['Onion', 1, 'piece'], ['Green Chilli', 2, 'piece'], ['Ginger', 10, 'g'], ['Mustard Seeds', 1, 'tsp'], ['Urad Dal', 10, 'g'], ['Salt', 5, 'g'], ['Cooking Oil', 2, 'tbsp'], ['Chana Dal', 10, 'g', true], ['Curry Leaves', 5, 'g', true], ['Cashew', 15, 'g', true], ['Ghee', 1, 'tbsp', true]],
            'steps' => ['Dry roast rava until fragrant and set aside.', 'Temper mustard, dals, cashew, curry leaves in oil; add onion, chilli, ginger.', 'Add 600 ml water and salt; bring to a boil.', 'Stir in rava slowly, cover and cook 3 minutes. Finish with ghee.'],
        ],
        [
            'name' => 'Paneer Butter Masala', 'meal_type' => 'dinner', 'cuisine' => 'North Indian', 'servings' => 3, 'prep_time' => 15, 'cook_time' => 25, 'is_veg' => true,
            'description' => 'Creamy tomato-cashew gravy with soft paneer cubes.',
            'ingredients' => [['Paneer', 250, 'g'], ['Tomato', 4, 'piece'], ['Onion', 1, 'piece'], ['Butter', 40, 'g'], ['Cashew', 20, 'g'], ['Ginger', 10, 'g'], ['Garlic', 10, 'g'], ['Red Chilli Powder', 1.5, 'tsp'], ['Garam Masala', 1, 'tsp'], ['Milk', 100, 'ml'], ['Salt', 5, 'g'], ['Sugar', 5, 'g', true]],
            'steps' => ['Cook onion, tomato, cashew, ginger and garlic; blend smooth.', 'Melt butter, add the purée and chilli powder; simmer 10 minutes.', 'Add milk, salt, sugar and garam masala.', 'Add paneer cubes and simmer 5 minutes.'],
        ],
        [
            'name' => 'Paneer Bhurji', 'meal_type' => 'breakfast', 'cuisine' => 'North Indian', 'servings' => 2, 'prep_time' => 10, 'cook_time' => 10, 'is_veg' => true,
            'description' => 'Scrambled paneer with onion, tomato and spices.',
            'ingredients' => [['Paneer', 200, 'g'], ['Onion', 1, 'piece'], ['Tomato', 1, 'piece'], ['Green Chilli', 1, 'piece'], ['Turmeric Powder', 0.25, 'tsp'], ['Red Chilli Powder', 0.5, 'tsp'], ['Garam Masala', 0.5, 'tsp'], ['Butter', 15, 'g'], ['Salt', 3, 'g'], ['Capsicum', 1, 'piece', true], ['Coriander Leaves', 5, 'g', true]],
            'steps' => ['Crumble the paneer.', 'Sauté onion, chilli and capsicum in butter; add tomato and spices.', 'Add paneer and salt; cook 3 minutes, stirring.', 'Garnish with coriander.'],
        ],
        [
            'name' => 'Ven Pongal', 'meal_type' => 'breakfast', 'cuisine' => 'South Indian', 'servings' => 3, 'prep_time' => 10, 'cook_time' => 25, 'is_veg' => true,
            'description' => 'Comforting rice and moong dal with pepper-cumin ghee.',
            'ingredients' => [['Rice', 150, 'g'], ['Moong Dal', 75, 'g'], ['Ghee', 3, 'tbsp'], ['Black Pepper', 1, 'tsp'], ['Cumin Seeds', 1, 'tsp'], ['Ginger', 10, 'g'], ['Salt', 5, 'g'], ['Cashew', 15, 'g', true], ['Curry Leaves', 3, 'g', true]],
            'steps' => ['Dry roast moong dal lightly.', 'Pressure cook rice and dal with 4x water until mushy; add salt.', 'Temper pepper, cumin, ginger, cashew and curry leaves in ghee.', 'Mix into the pongal and serve hot.'],
        ],
        [
            'name' => 'Lemon Rice', 'meal_type' => 'lunch', 'cuisine' => 'South Indian', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Bright, tangy rice with a crunchy dal tempering.',
            'ingredients' => [['Rice', 150, 'g'], ['Lemon', 1, 'piece'], ['Green Chilli', 2, 'piece'], ['Mustard Seeds', 1, 'tsp'], ['Chana Dal', 10, 'g'], ['Urad Dal', 5, 'g'], ['Turmeric Powder', 0.25, 'tsp'], ['Salt', 3, 'g'], ['Cooking Oil', 2, 'tbsp'], ['Curry Leaves', 3, 'g', true], ['Hing', 0.25, 'tsp', true], ['Cashew', 15, 'g', true]],
            'steps' => ['Cook rice and let it cool.', 'Temper mustard, dals, chilli, cashew, curry leaves, hing and turmeric in oil.', 'Mix tempering, lemon juice and salt into the rice.'],
        ],
        [
            'name' => 'Potato Poriyal', 'meal_type' => 'lunch', 'cuisine' => 'South Indian', 'servings' => 3, 'prep_time' => 10, 'cook_time' => 20, 'is_veg' => true,
            'description' => 'Crispy spiced potato stir-fry, great with sambar rice.',
            'ingredients' => [['Potato', 4, 'piece'], ['Onion', 1, 'piece'], ['Mustard Seeds', 1, 'tsp'], ['Urad Dal', 5, 'g'], ['Turmeric Powder', 0.25, 'tsp'], ['Red Chilli Powder', 1, 'tsp'], ['Salt', 4, 'g'], ['Cooking Oil', 3, 'tbsp'], ['Curry Leaves', 3, 'g', true]],
            'steps' => ['Boil, peel and cube the potatoes.', 'Temper mustard, urad dal and curry leaves; sauté onion.', 'Add potatoes, spices and salt; roast until crisp.'],
        ],
        [
            'name' => 'Masala Chai', 'meal_type' => 'snack', 'cuisine' => 'Indian', 'servings' => 2, 'prep_time' => 2, 'cook_time' => 8, 'is_veg' => true,
            'description' => 'Strong milky tea with ginger.',
            'ingredients' => [['Milk', 250, 'ml'], ['Tea Powder', 8, 'g'], ['Sugar', 15, 'g'], ['Ginger', 5, 'g', true]],
            'steps' => ['Boil 150 ml water with crushed ginger and tea powder.', 'Add milk and sugar; simmer 3 minutes.', 'Strain and serve.'],
        ],
    ];

    public function run(): void
    {
        $ingredients = Ingredient::pluck('id', 'name');

        foreach (self::RECIPES as $data) {
            $recipe = Recipe::updateOrCreate(
                ['household_id' => null, 'name' => $data['name']],
                collect($data)->except(['ingredients', 'steps'])->all(),
            );

            $recipe->ingredients()->delete();
            foreach ($data['ingredients'] as $row) {
                $recipe->ingredients()->create([
                    'ingredient_id' => $ingredients[$row[0]] ?? throw new \RuntimeException("Unknown ingredient {$row[0]}"),
                    'quantity' => $row[1],
                    'unit' => $row[2],
                    'optional' => $row[3] ?? false,
                ]);
            }

            $recipe->steps()->delete();
            foreach ($data['steps'] as $i => $text) {
                $recipe->steps()->create(['position' => $i + 1, 'text' => $text]);
            }
        }
    }
}
