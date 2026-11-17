<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Support\Nutrition;
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
        [
            'name' => 'Moong Sprouts Salad', 'meal_type' => 'breakfast', 'cuisine' => 'South Indian', 'servings' => 2, 'prep_time' => 10, 'cook_time' => 0, 'is_veg' => true,
            'description' => 'Crunchy sprouted green gram with cucumber, tomato and lemon.',
            'ingredients' => [['Green Gram', 100, 'g'], ['Onion', 1, 'piece'], ['Tomato', 1, 'piece'], ['Cucumber', 1, 'piece'], ['Lemon', 1, 'piece'], ['Salt', 2, 'g'], ['Coriander Leaves', 10, 'g', true], ['Green Chilli', 1, 'piece', true]],
            'steps' => ['Soak green gram overnight, drain and keep covered for a day to sprout.', 'Chop onion, tomato, cucumber, chilli and coriander.', 'Toss the sprouts with the vegetables, salt and lemon juice.'],
        ],
        [
            'name' => 'Chana Sundal', 'meal_type' => 'snack', 'cuisine' => 'South Indian', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 20, 'is_veg' => true,
            'description' => 'Temple-style chickpea sundal with coconut and curry leaves.',
            'ingredients' => [['Chickpeas', 150, 'g'], ['Mustard Seeds', 0.5, 'tsp'], ['Urad Dal', 5, 'g'], ['Green Chilli', 1, 'piece'], ['Salt', 3, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Coconut', 0.25, 'piece', true], ['Curry Leaves', 3, 'g', true], ['Hing', 0.25, 'tsp', true]],
            'steps' => ['Soak chickpeas overnight and pressure cook with salt until soft.', 'Temper mustard, urad dal, chilli, curry leaves and hing in oil.', 'Add chickpeas and grated coconut; toss well.'],
        ],
        [
            'name' => 'Ragi Dosa', 'meal_type' => 'breakfast', 'cuisine' => 'South Indian', 'servings' => 3, 'prep_time' => 10, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Instant finger-millet dosa, crisp and filling.',
            'ingredients' => [['Ragi Flour', 150, 'g'], ['Rava', 30, 'g'], ['Curd', 50, 'g'], ['Onion', 1, 'piece'], ['Green Chilli', 1, 'piece'], ['Cumin Seeds', 0.5, 'tsp'], ['Salt', 3, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Curry Leaves', 3, 'g', true]],
            'steps' => ['Mix ragi flour, rava, curd, salt and water into a thin batter; rest 10 minutes.', 'Stir in chopped onion, chilli, cumin and curry leaves.', 'Pour thin dosas on a hot tawa, drizzle oil and cook until crisp.'],
        ],
        [
            'name' => 'Masala Oats', 'meal_type' => 'breakfast', 'cuisine' => 'Indian', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 10, 'is_veg' => true,
            'description' => 'Savoury oats with vegetables — quick and light.',
            'ingredients' => [['Oats', 100, 'g'], ['Onion', 1, 'piece'], ['Tomato', 1, 'piece'], ['Carrot', 1, 'piece'], ['Turmeric Powder', 0.25, 'tsp'], ['Salt', 3, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Green Peas', 30, 'g', true]],
            'steps' => ['Sauté onion, carrot and peas in oil.', 'Add tomato, turmeric and salt; cook 2 minutes.', 'Add oats and 400 ml water; simmer until creamy.'],
        ],
        [
            'name' => 'Keerai Kootu', 'meal_type' => 'lunch', 'cuisine' => 'South Indian', 'servings' => 3, 'prep_time' => 10, 'cook_time' => 20, 'is_veg' => true,
            'description' => 'Spinach and moong dal kootu, gently spiced.',
            'ingredients' => [['Spinach', 250, 'g'], ['Moong Dal', 100, 'g'], ['Onion', 1, 'piece'], ['Green Chilli', 1, 'piece'], ['Cumin Seeds', 0.5, 'tsp'], ['Turmeric Powder', 0.25, 'tsp'], ['Salt', 4, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Coconut', 0.25, 'piece', true]],
            'steps' => ['Cook moong dal with turmeric until soft.', 'Sauté onion and chilli, add chopped spinach and cook down.', 'Mix in dal, salt and ground coconut-cumin; simmer 5 minutes.'],
        ],
        [
            'name' => 'Pepper Chicken', 'meal_type' => 'dinner', 'cuisine' => 'Chettinad', 'servings' => 4, 'prep_time' => 10, 'cook_time' => 25, 'is_veg' => false,
            'description' => 'Dry chicken roast with crushed black pepper and curry leaves.',
            'ingredients' => [['Chicken', 500, 'g'], ['Onion', 2, 'piece'], ['Black Pepper', 2, 'tsp'], ['Ginger', 10, 'g'], ['Garlic', 10, 'g'], ['Turmeric Powder', 0.25, 'tsp'], ['Salt', 6, 'g'], ['Cooking Oil', 2, 'tbsp'], ['Curry Leaves', 5, 'g', true]],
            'steps' => ['Marinate chicken with turmeric, salt and ginger-garlic.', 'Sauté onions and curry leaves until golden.', 'Add chicken, cover and cook 15 minutes; finish with crushed pepper and roast dry.'],
        ],
        [
            'name' => 'Paneer Tikka', 'meal_type' => 'dinner', 'cuisine' => 'North Indian', 'servings' => 3, 'prep_time' => 20, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Pan-grilled paneer and capsicum in spiced curd.',
            'ingredients' => [['Paneer', 250, 'g'], ['Curd', 100, 'g'], ['Capsicum', 1, 'piece'], ['Onion', 1, 'piece'], ['Red Chilli Powder', 1, 'tsp'], ['Garam Masala', 0.5, 'tsp'], ['Salt', 3, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Lemon', 0.5, 'piece', true]],
            'steps' => ['Mix curd, chilli, garam masala and salt; coat paneer, capsicum and onion.', 'Rest 15 minutes.', 'Grill on a hot pan with a little oil until charred at the edges.'],
        ],
        [
            'name' => 'Fish Curry', 'meal_type' => 'dinner', 'cuisine' => 'South Indian', 'servings' => 4, 'prep_time' => 15, 'cook_time' => 25, 'is_veg' => false,
            'description' => 'Tangy tomato-based fish kuzhambu.',
            'ingredients' => [['Fish', 500, 'g'], ['Tomato', 3, 'piece'], ['Onion', 1, 'piece'], ['Red Chilli Powder', 2, 'tsp'], ['Coriander Powder', 2, 'tsp'], ['Turmeric Powder', 0.5, 'tsp'], ['Mustard Seeds', 1, 'tsp'], ['Salt', 6, 'g'], ['Cooking Oil', 2, 'tbsp'], ['Curry Leaves', 5, 'g', true]],
            'steps' => ['Temper mustard and curry leaves; sauté onion.', 'Add tomatoes and spice powders with a cup of water; simmer 10 minutes.', 'Slide in the fish and cook gently for 8 minutes.'],
        ],
        // Family plans: lunch box, quick weekday and prep-ahead dishes.
        [
            'name' => 'Coconut Rice', 'meal_type' => 'lunch', 'cuisine' => 'South Indian', 'servings' => 3, 'prep_time' => 10, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Rice tossed with fresh grated coconut and a crunchy dal tempering. A lunch box favourite.',
            'ingredients' => [['Rice', 300, 'g'], ['Coconut', 0.5, 'piece'], ['Urad Dal', 10, 'g'], ['Chana Dal', 10, 'g'], ['Mustard Seeds', 0.5, 'tsp'], ['Curry Leaves', 5, 'g'], ['Green Chilli', 2, 'piece'], ['Cooking Oil', 2, 'tbsp'], ['Salt', 5, 'g'], ['Cashew', 15, 'g', true]],
            'steps' => ['Cook the rice and spread it out to cool.', 'Heat oil; fry mustard, urad dal, chana dal, cashew, chilli and curry leaves.', 'Add the grated coconut and stir for a minute.', 'Mix in the rice and salt gently.'],
        ],
        [
            'name' => 'Jeera Rice', 'meal_type' => 'lunch', 'cuisine' => 'North Indian', 'servings' => 3, 'prep_time' => 5, 'cook_time' => 20, 'is_veg' => true,
            'description' => 'Fragrant basmati rice with ghee and cumin.',
            'ingredients' => [['Basmati Rice', 300, 'g'], ['Cumin Seeds', 1.5, 'tsp'], ['Ghee', 2, 'tbsp'], ['Salt', 5, 'g'], ['Water', 600, 'ml']],
            'steps' => ['Wash and soak the rice for 15 minutes.', 'Heat ghee and fry the cumin until it crackles.', 'Add rice, water and salt; cook covered until done.', 'Fluff with a fork.'],
        ],
        [
            'name' => 'Veg Pulao', 'meal_type' => 'lunch', 'cuisine' => 'North Indian', 'servings' => 3, 'prep_time' => 15, 'cook_time' => 20, 'is_veg' => true,
            'description' => 'Mild one-pot rice with carrot and peas.',
            'ingredients' => [['Basmati Rice', 300, 'g'], ['Carrot', 1, 'piece'], ['Green Peas', 80, 'g'], ['Onion', 1, 'piece'], ['Ginger', 5, 'g'], ['Garam Masala', 1, 'tsp'], ['Ghee', 2, 'tbsp'], ['Salt', 5, 'g'], ['Water', 600, 'ml']],
            'steps' => ['Soak the rice for 15 minutes.', 'Fry onion and ginger in ghee; add carrot, peas and garam masala.', 'Add rice, water and salt; cook covered until done.'],
        ],
        [
            'name' => 'Idli', 'meal_type' => 'breakfast', 'cuisine' => 'South Indian', 'servings' => 4, 'prep_time' => 5, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Soft steamed idlis from ready batter.',
            'ingredients' => [['Idli Dosa Batter', 500, 'g'], ['Cooking Oil', 1, 'tsp']],
            'steps' => ['Grease the idli plates.', 'Pour in the batter.', 'Steam for 10–12 minutes until a toothpick comes out clean.'],
        ],
        [
            'name' => 'Dosa', 'meal_type' => 'breakfast', 'cuisine' => 'South Indian', 'servings' => 4, 'prep_time' => 5, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Crisp dosas from ready batter.',
            'ingredients' => [['Idli Dosa Batter', 500, 'g'], ['Cooking Oil', 3, 'tbsp']],
            'steps' => ['Heat a tawa and wipe it with a little oil.', 'Spread a ladle of batter in a thin circle.', 'Drizzle oil around the edge and cook until golden.'],
        ],
        [
            'name' => 'Veg Cheese Sandwich', 'meal_type' => 'snack', 'cuisine' => 'Indian', 'servings' => 2, 'prep_time' => 10, 'cook_time' => 5, 'is_veg' => true,
            'description' => 'Buttered sandwich with cucumber, tomato and cheese.',
            'ingredients' => [['Bread', 0.25, 'packet'], ['Cheese', 40, 'g'], ['Cucumber', 0.5, 'piece'], ['Tomato', 1, 'piece'], ['Butter', 15, 'g'], ['Black Pepper', 0.25, 'tsp', true]],
            'steps' => ['Butter the bread slices.', 'Layer cucumber, tomato and cheese; sprinkle pepper.', 'Toast on a tawa until golden on both sides.'],
        ],
        [
            'name' => 'Egg Sandwich', 'meal_type' => 'snack', 'cuisine' => 'Indian', 'servings' => 2, 'prep_time' => 10, 'cook_time' => 10, 'is_veg' => false,
            'description' => 'Boiled egg sandwich with butter and pepper.',
            'ingredients' => [['Bread', 0.25, 'packet'], ['Egg', 2, 'piece'], ['Butter', 10, 'g'], ['Black Pepper', 0.25, 'tsp'], ['Salt', 1, 'g']],
            'steps' => ['Boil the eggs for 10 minutes, cool and slice.', 'Butter the bread and layer the egg slices.', 'Season with salt and pepper and close.'],
        ],
        [
            'name' => 'Paneer Roll', 'meal_type' => 'lunch', 'cuisine' => 'North Indian', 'servings' => 2, 'prep_time' => 15, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Chapati rolled around spiced paneer and capsicum.',
            'ingredients' => [['Wheat Flour', 150, 'g'], ['Paneer', 100, 'g'], ['Capsicum', 0.5, 'piece'], ['Onion', 0.5, 'piece'], ['Tomato Ketchup', 1, 'tbsp'], ['Garam Masala', 0.5, 'tsp'], ['Cooking Oil', 1, 'tbsp'], ['Salt', 2, 'g']],
            'steps' => ['Make soft chapatis.', 'Sauté onion and capsicum; add crumbled paneer, garam masala and salt.', 'Spread ketchup on each chapati, add the filling and roll tightly.'],
        ],
        [
            'name' => 'Semiya Upma', 'meal_type' => 'breakfast', 'cuisine' => 'South Indian', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Vermicelli upma with carrot and peas.',
            'ingredients' => [['Vermicelli', 150, 'g'], ['Onion', 1, 'piece'], ['Carrot', 0.5, 'piece'], ['Green Peas', 30, 'g'], ['Mustard Seeds', 0.5, 'tsp'], ['Curry Leaves', 3, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Salt', 3, 'g'], ['Water', 400, 'ml']],
            'steps' => ['Roast the vermicelli until light golden; keep aside.', 'Temper mustard and curry leaves; sauté onion, carrot and peas.', 'Add water and salt; when it boils, add vermicelli and cook covered until soft.'],
        ],
        [
            'name' => 'Aval Upma', 'meal_type' => 'breakfast', 'cuisine' => 'South Indian', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 10, 'is_veg' => true,
            'description' => 'Light poha with peanuts, onion and lemon.',
            'ingredients' => [['Poha', 150, 'g'], ['Onion', 1, 'piece'], ['Peanuts', 20, 'g'], ['Mustard Seeds', 0.5, 'tsp'], ['Turmeric Powder', 0.25, 'tsp'], ['Curry Leaves', 3, 'g'], ['Green Chilli', 1, 'piece'], ['Lemon', 0.5, 'piece'], ['Cooking Oil', 1, 'tbsp'], ['Salt', 3, 'g']],
            'steps' => ['Rinse the poha and drain.', 'Fry peanuts, mustard, curry leaves and chilli; add onion and turmeric.', 'Add poha and salt, mix gently and steam 2 minutes; squeeze lemon.'],
        ],
        [
            'name' => 'Bread Omelette', 'meal_type' => 'breakfast', 'cuisine' => 'Indian', 'servings' => 1, 'prep_time' => 5, 'cook_time' => 5, 'is_veg' => false,
            'description' => 'Street-style omelette folded into toasted bread.',
            'ingredients' => [['Bread', 0.125, 'packet'], ['Egg', 2, 'piece'], ['Onion', 0.25, 'piece'], ['Green Chilli', 1, 'piece'], ['Butter', 10, 'g'], ['Salt', 1, 'g']],
            'steps' => ['Beat eggs with onion, chilli and salt.', 'Pour into a buttered pan and place the bread slices on top.', 'Flip, fold the omelette over the bread and toast both sides.'],
        ],
        [
            'name' => 'Egg Curry', 'meal_type' => 'dinner', 'cuisine' => 'South Indian', 'servings' => 3, 'prep_time' => 10, 'cook_time' => 20, 'is_veg' => false,
            'description' => 'Boiled eggs in onion-tomato masala. Tastes even better the next day.',
            'ingredients' => [['Egg', 6, 'piece'], ['Onion', 2, 'piece'], ['Tomato', 2, 'piece'], ['Ginger', 5, 'g'], ['Garlic', 5, 'g'], ['Red Chilli Powder', 1, 'tsp'], ['Coriander Powder', 1, 'tsp'], ['Garam Masala', 0.5, 'tsp'], ['Turmeric Powder', 0.25, 'tsp'], ['Cooking Oil', 2, 'tbsp'], ['Salt', 4, 'g']],
            'steps' => ['Boil and peel the eggs; make small slits.', 'Fry onion, ginger and garlic; add tomato and the spice powders.', 'Add a cup of water and the eggs; simmer 10 minutes.'],
        ],
        [
            'name' => 'Dal Khichdi', 'meal_type' => 'dinner', 'cuisine' => 'Indian', 'servings' => 3, 'prep_time' => 5, 'cook_time' => 20, 'is_veg' => true,
            'description' => 'Comforting one-pot rice and moong dal. Easy for kids and tired evenings.',
            'ingredients' => [['Rice', 150, 'g'], ['Moong Dal', 100, 'g'], ['Ghee', 1, 'tbsp'], ['Cumin Seeds', 1, 'tsp'], ['Turmeric Powder', 0.25, 'tsp'], ['Ginger', 5, 'g'], ['Salt', 4, 'g'], ['Water', 900, 'ml']],
            'steps' => ['Wash rice and dal together.', 'Fry cumin and ginger in ghee in a pressure cooker.', 'Add rice, dal, turmeric, salt and water; cook for 3 whistles.'],
        ],
        [
            'name' => 'Tomato Rasam', 'meal_type' => 'lunch', 'cuisine' => 'South Indian', 'servings' => 4, 'prep_time' => 5, 'cook_time' => 15, 'is_veg' => true,
            'description' => 'Peppery tomato rasam. Keeps well for two days.',
            'ingredients' => [['Tomato', 3, 'piece'], ['Toor Dal', 30, 'g'], ['Black Pepper', 1, 'tsp'], ['Cumin Seeds', 1, 'tsp'], ['Garlic', 5, 'g'], ['Mustard Seeds', 0.5, 'tsp'], ['Hing', 0.25, 'tsp'], ['Curry Leaves', 3, 'g'], ['Ghee', 1, 'tsp'], ['Salt', 4, 'g'], ['Water', 800, 'ml'], ['Coriander Leaves', 5, 'g', true]],
            'steps' => ['Cook the toor dal soft.', 'Crush pepper, cumin and garlic.', 'Boil tomatoes with water, salt, hing and the crushed spices; add the dal water.', 'Temper mustard and curry leaves in ghee and pour over.'],
        ],
        [
            'name' => 'Sambar', 'meal_type' => 'lunch', 'cuisine' => 'South Indian', 'servings' => 4, 'prep_time' => 10, 'cook_time' => 30, 'is_veg' => true,
            'description' => 'Everyday toor dal sambar with vegetables. Make it on Sunday for two days.',
            'ingredients' => [['Toor Dal', 120, 'g'], ['Tomato', 2, 'piece'], ['Onion', 1, 'piece'], ['Carrot', 1, 'piece'], ['Red Chilli Powder', 1, 'tsp'], ['Coriander Powder', 2, 'tsp'], ['Turmeric Powder', 0.25, 'tsp'], ['Mustard Seeds', 0.5, 'tsp'], ['Hing', 0.25, 'tsp'], ['Curry Leaves', 3, 'g'], ['Cooking Oil', 1, 'tbsp'], ['Salt', 5, 'g'], ['Water', 800, 'ml']],
            'steps' => ['Pressure cook the dal with turmeric.', 'Boil onion, carrot and tomato with the spice powders and salt.', 'Add the mashed dal and simmer 5 minutes.', 'Temper mustard, hing and curry leaves and pour over.'],
        ],
        [
            'name' => 'Banana Milkshake', 'meal_type' => 'snack', 'cuisine' => 'Indian', 'servings' => 2, 'prep_time' => 5, 'cook_time' => 0, 'is_veg' => true,
            'description' => 'Thick banana milkshake. An after-school favourite.',
            'ingredients' => [['Banana', 2, 'piece'], ['Milk', 300, 'ml'], ['Sugar', 10, 'g'], ['Cashew', 10, 'g', true]],
            'steps' => ['Blend banana, chilled milk, sugar and cashew until smooth.', 'Serve right away.'],
        ],
    ];

    /** Estimated nutrition per serving: [kcal, protein g, carbs g, fat g, fiber g]. */
    private const NUTRITION = [
        'Omelette' => [210, 13, 4, 16, 1],
        'Egg Fried Rice' => [520, 17, 80, 15, 3],
        'Chicken Fried Rice' => [600, 36, 80, 15, 3],
        'Chicken Curry' => [300, 30, 10, 16, 3],
        'Tomato Rice' => [440, 8, 80, 11, 4],
        'Curd Rice' => [330, 10, 55, 8, 1],
        'Dal' => [230, 11, 30, 8, 7],
        'Chapati' => [280, 9, 55, 5, 8],
        'Upma' => [330, 8, 50, 11, 3],
        'Paneer Butter Masala' => [420, 17, 16, 32, 3],
        'Paneer Bhurji' => [340, 20, 9, 25, 2],
        'Ven Pongal' => [360, 11, 52, 12, 4],
        'Lemon Rice' => [400, 7, 70, 11, 2],
        'Potato Poriyal' => [220, 3, 30, 10, 3],
        'Masala Chai' => [110, 4, 13, 4, 0],
        'Moong Sprouts Salad' => [200, 13, 32, 2, 8],
        'Chana Sundal' => [290, 15, 40, 9, 11],
        'Ragi Dosa' => [260, 7, 45, 6, 7],
        'Masala Oats' => [250, 9, 38, 8, 6],
        'Keerai Kootu' => [190, 11, 24, 6, 7],
        'Pepper Chicken' => [260, 30, 6, 13, 2],
        'Paneer Tikka' => [290, 18, 9, 20, 2],
        'Fish Curry' => [240, 26, 8, 11, 2],
        'Coconut Rice' => [380, 7, 52, 16, 4],
        'Jeera Rice' => [320, 6, 58, 7, 1],
        'Veg Pulao' => [360, 8, 60, 9, 4],
        'Idli' => [180, 6, 36, 1, 2],
        'Dosa' => [220, 5, 34, 7, 1],
        'Veg Cheese Sandwich' => [310, 12, 34, 14, 3],
        'Egg Sandwich' => [290, 14, 30, 12, 2],
        'Paneer Roll' => [420, 18, 50, 16, 5],
        'Semiya Upma' => [300, 7, 52, 7, 3],
        'Aval Upma' => [280, 6, 45, 9, 3],
        'Bread Omelette' => [330, 15, 26, 18, 1],
        'Egg Curry' => [260, 14, 9, 18, 2],
        'Dal Khichdi' => [290, 11, 48, 6, 4],
        'Tomato Rasam' => [70, 3, 10, 2, 2],
        'Sambar' => [150, 8, 22, 4, 6],
        'Banana Milkshake' => [210, 7, 36, 5, 2],
    ];

    /** Tamil name and description per built-in recipe. */
    private const TAMIL = [
        'Omelette' => ['ஆம்லெட்', 'வெங்காயம், பச்சை மிளகாய் போட்ட மசாலா ஆம்லெட்.'],
        'Egg Fried Rice' => ['எக் ஃப்ரைடு ரைஸ்', 'முட்டை போட்ட ஃப்ரைடு ரைஸ் — சீக்கிரம் ரெடி.'],
        'Chicken Fried Rice' => ['சிக்கன் ஃப்ரைடு ரைஸ்', 'சிக்கன், முட்டை, காய்கறி நிறைஞ்ச ஃப்ரைடு ரைஸ்.'],
        'Chicken Curry' => ['சிக்கன் குழம்பு', 'வெங்காயம்-தக்காளி மசாலால வீட்டு ஸ்டைல் காரமான சிக்கன் குழம்பு.'],
        'Tomato Rice' => ['தக்காளி சாதம்', 'லஞ்ச் பாக்ஸுக்கு ஏத்த புளிப்பான தக்காளி சாதம்.'],
        'Curd Rice' => ['தயிர் சாதம்', 'கடுகு, கறிவேப்பிலை தாளிச்ச குளுகுளு தயிர் சாதம்.'],
        'Dal' => ['பருப்பு', 'நெய், சீரகம் தாளிச்ச சிம்பிள் துவரம்பருப்பு.'],
        'Chapati' => ['சப்பாத்தி', 'சாஃப்டான கோதுமை சப்பாத்தி (சுமார் 8).'],
        'Upma' => ['உப்புமா', 'வெங்காயம், முந்திரி போட்ட ரவா உப்புமா.'],
        'Paneer Butter Masala' => ['பன்னீர் பட்டர் மசாலா', 'தக்காளி-முந்திரி கிரேவில சாஃப்டான பன்னீர்.'],
        'Paneer Bhurji' => ['பன்னீர் புர்ஜி', 'வெங்காயம், தக்காளியோட உதிர்த்த பன்னீர்.'],
        'Ven Pongal' => ['வெண் பொங்கல்', 'மிளகு, சீரகம், நெய் போட்ட அரிசி-பருப்பு பொங்கல்.'],
        'Lemon Rice' => ['எலுமிச்சை சாதம்', 'பருப்பு தாளிச்ச புளிப்பான எலுமிச்சை சாதம்.'],
        'Potato Poriyal' => ['உருளைக்கிழங்கு பொரியல்', 'சாம்பார் சாதத்துக்கு சூப்பரான மொறுமொறு உருளைக்கிழங்கு பொரியல்.'],
        'Masala Chai' => ['மசாலா டீ', 'இஞ்சி போட்ட ஸ்ட்ராங் பால் டீ.'],
        'Moong Sprouts Salad' => ['முளைகட்டிய பயறு சாலட்', 'வெள்ளரி, தக்காளி, எலுமிச்சை போட்ட முளைகட்டிய பச்சை பயறு.'],
        'Chana Sundal' => ['கொண்டைக்கடலை சுண்டல்', 'தேங்காய், கறிவேப்பிலை போட்ட கோயில் ஸ்டைல் சுண்டல்.'],
        'Ragi Dosa' => ['ராகி தோசை', 'டக்குன்னு ரெடியாகுற மொறுமொறு ராகி தோசை.'],
        'Masala Oats' => ['மசாலா ஓட்ஸ்', 'காய்கறி போட்ட காரமான ஓட்ஸ் — சீக்கிரமும் லைட்டும்.'],
        'Keerai Kootu' => ['கீரை கூட்டு', 'கீரையும் பாசிப்பருப்பும் போட்ட மிதமான கூட்டு.'],
        'Pepper Chicken' => ['பெப்பர் சிக்கன்', 'மிளகு, கறிவேப்பிலையோட வறுத்த செட்டிநாடு ஸ்டைல் சிக்கன்.'],
        'Paneer Tikka' => ['பன்னீர் டிக்கா', 'மசாலா தயிர்ல ஊற வெச்ச பன்னீர், குடைமிளகாய் — தவால வாட்டினது.'],
        'Fish Curry' => ['மீன் குழம்பு', 'தக்காளி போட்ட புளிப்பான மீன் குழம்பு.'],
        'Coconut Rice' => ['தேங்காய் சாதம்', 'துருவின தேங்காய், பருப்பு தாளிச்ச சாதம் — லஞ்ச் பாக்ஸ்க்கு சூப்பர்.'],
        'Jeera Rice' => ['ஜீரா சாதம்', 'நெய், சீரகம் போட்ட வாசனையான பாசுமதி சாதம்.'],
        'Veg Pulao' => ['வெஜ் புலாவ்', 'கேரட், பட்டாணி போட்ட மிதமான ஒரே பாத்திர சாதம்.'],
        'Idli' => ['இட்லி', 'ரெடிமேட் மாவுல ஆவியில வேக வெச்ச மெதுவான இட்லி.'],
        'Dosa' => ['தோசை', 'ரெடிமேட் மாவுல மொறுமொறு தோசை.'],
        'Veg Cheese Sandwich' => ['வெஜ் சீஸ் சாண்ட்விச்', 'வெள்ளரி, தக்காளி, சீஸ் வெச்ச பட்டர் சாண்ட்விச்.'],
        'Egg Sandwich' => ['முட்டை சாண்ட்விச்', 'அவிச்ச முட்டை, பட்டர், மிளகு வெச்ச சாண்ட்விச்.'],
        'Paneer Roll' => ['பன்னீர் ரோல்', 'மசாலா பன்னீர், குடைமிளகாய் வெச்சு சுருட்டின சப்பாத்தி.'],
        'Semiya Upma' => ['சேமியா உப்புமா', 'கேரட், பட்டாணி போட்ட சேமியா உப்புமா.'],
        'Aval Upma' => ['அவல் உப்புமா', 'வேர்க்கடலை, வெங்காயம், எலுமிச்சை போட்ட லைட்டான அவல்.'],
        'Bread Omelette' => ['பிரெட் ஆம்லெட்', 'டோஸ்ட் பண்ண பிரெட்ல மடிச்ச ரோட்டுக்கடை ஸ்டைல் ஆம்லெட்.'],
        'Egg Curry' => ['முட்டை குழம்பு', 'வெங்காயம்-தக்காளி மசாலால அவிச்ச முட்டை — மறுநாள் இன்னும் டேஸ்ட்.'],
        'Dal Khichdi' => ['பருப்பு கிச்சடி', 'அரிசி, பாசிப்பருப்பு ஒரே பாத்திரத்துல — குழந்தைகளுக்கும் களைப்பான சாயங்காலத்துக்கும் ஈஸி.'],
        'Tomato Rasam' => ['தக்காளி ரசம்', 'மிளகு தூக்கலான தக்காளி ரசம் — ரெண்டு நாள் நல்லா இருக்கும்.'],
        'Sambar' => ['சாம்பார்', 'காய்கறி போட்ட தினசரி துவரம்பருப்பு சாம்பார் — ஞாயிறு செஞ்சா ரெண்டு நாளுக்கு.'],
        'Banana Milkshake' => ['வாழைப்பழ மில்க் ஷேக்', 'கெட்டியான வாழைப்பழ மில்க் ஷேக் — ஸ்கூல் முடிஞ்சு வந்ததும் பிடிக்கும்.'],
    ];

    /**
     * lunchbox: packs well and is still good at noon; kids: mild and kid-friendly;
     * prep_ahead: can be made the day before (Sunday prep) and keeps.
     */
    public const TAGS = [
        'Omelette' => ['kids'], 'Egg Fried Rice' => ['lunchbox', 'kids'], 'Chicken Fried Rice' => ['lunchbox'], 'Chicken Curry' => ['prep_ahead'],
        'Tomato Rice' => ['lunchbox', 'kids'], 'Curd Rice' => ['lunchbox', 'kids'], 'Dal' => ['prep_ahead', 'kids'], 'Chapati' => ['lunchbox', 'kids'],
        'Upma' => ['lunchbox', 'kids'], 'Paneer Butter Masala' => ['kids'], 'Paneer Bhurji' => ['lunchbox', 'kids'], 'Ven Pongal' => ['kids'],
        'Lemon Rice' => ['lunchbox', 'kids'], 'Potato Poriyal' => ['lunchbox', 'kids'], 'Chana Sundal' => ['lunchbox', 'kids'], 'Ragi Dosa' => ['kids'],
        'Keerai Kootu' => ['prep_ahead'], 'Fish Curry' => ['prep_ahead'], 'Paneer Tikka' => ['kids'],
        'Coconut Rice' => ['lunchbox', 'kids'], 'Jeera Rice' => ['lunchbox', 'kids'], 'Veg Pulao' => ['lunchbox', 'kids'],
        'Idli' => ['lunchbox', 'kids'], 'Dosa' => ['kids'], 'Veg Cheese Sandwich' => ['lunchbox', 'kids'], 'Egg Sandwich' => ['lunchbox', 'kids'],
        'Paneer Roll' => ['lunchbox', 'kids'], 'Semiya Upma' => ['lunchbox', 'kids'], 'Aval Upma' => ['lunchbox', 'kids'],
        'Bread Omelette' => ['lunchbox', 'kids'], 'Egg Curry' => ['prep_ahead'], 'Dal Khichdi' => ['kids'],
        'Tomato Rasam' => ['prep_ahead'], 'Sambar' => ['prep_ahead'], 'Banana Milkshake' => ['kids'],
    ];

    public function run(): void
    {
        $ingredients = Ingredient::pluck('id', 'name');

        foreach (self::RECIPES as $data) {
            $recipe = Recipe::updateOrCreate(
                ['household_id' => null, 'name' => $data['name']],
                [
                    ...collect($data)->except(['ingredients', 'steps'])->all(),
                    ...array_combine(Nutrition::FIELDS, self::NUTRITION[$data['name']]),
                    'tags' => self::TAGS[$data['name']] ?? [],
                    'name_ta' => self::TAMIL[$data['name']][0],
                    'description_ta' => self::TAMIL[$data['name']][1],
                ],
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
