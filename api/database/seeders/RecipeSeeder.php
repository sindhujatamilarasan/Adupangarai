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
    ];

    /** Tamil name and description per built-in recipe. */
    private const TAMIL = [
        'Omelette' => ['ஆம்லெட்', 'வெங்காயம், பச்சை மிளகாய் சேர்த்த மசாலா ஆம்லெட்.'],
        'Egg Fried Rice' => ['முட்டை ஃப்ரைடு ரைஸ்', 'முட்டை சேர்த்த விரைவான ஃப்ரைடு ரைஸ்.'],
        'Chicken Fried Rice' => ['சிக்கன் ஃப்ரைடு ரைஸ்', 'கோழிக்கறி, முட்டை, காய்கறிகள் நிறைந்த ஃப்ரைடு ரைஸ்.'],
        'Chicken Curry' => ['கோழிக் குழம்பு', 'வெங்காயம்-தக்காளி மசாலாவில் வீட்டு முறை காரமான கோழிக் குழம்பு.'],
        'Tomato Rice' => ['தக்காளி சாதம்', 'மதிய உணவுப் பெட்டிக்கு ஏற்ற புளிப்பான தக்காளி சாதம்.'],
        'Curd Rice' => ['தயிர் சாதம்', 'கடுகு, கறிவேப்பிலை தாளித்த குளிர்ச்சியான தயிர் சாதம்.'],
        'Dal' => ['பருப்பு', 'நெய்-சீரகத் தாளிப்புடன் எளிய துவரம் பருப்பு.'],
        'Chapati' => ['சப்பாத்தி', 'மென்மையான முழு கோதுமை சப்பாத்தி (சுமார் 8).'],
        'Upma' => ['உப்புமா', 'வெங்காயம், முந்திரி சேர்த்த ரவா உப்புமா.'],
        'Paneer Butter Masala' => ['பன்னீர் பட்டர் மசாலா', 'தக்காளி-முந்திரி கிரேவியில் மென்மையான பன்னீர்.'],
        'Paneer Bhurji' => ['பன்னீர் புர்ஜி', 'வெங்காயம், தக்காளியுடன் உதிர்த்த பன்னீர்.'],
        'Ven Pongal' => ['வெண் பொங்கல்', 'மிளகு-சீரக நெய்யுடன் அரிசியும் பாசிப் பருப்பும்.'],
        'Lemon Rice' => ['எலுமிச்சை சாதம்', 'பருப்புத் தாளிப்புடன் புளிப்பான எலுமிச்சை சாதம்.'],
        'Potato Poriyal' => ['உருளைக்கிழங்கு பொரியல்', 'சாம்பார் சாதத்துக்கு ஏற்ற மொறுமொறு உருளைக்கிழங்கு பொரியல்.'],
        'Masala Chai' => ['மசாலா டீ', 'இஞ்சி சேர்த்த திடமான பால் டீ.'],
        'Moong Sprouts Salad' => ['பச்சைப் பயறு முளைகட்டிய சாலட்', 'வெள்ளரி, தக்காளி, எலுமிச்சையுடன் முளைகட்டிய பச்சைப் பயறு.'],
        'Chana Sundal' => ['கொண்டைக்கடலை சுண்டல்', 'தேங்காய், கறிவேப்பிலையுடன் கோயில் முறை சுண்டல்.'],
        'Ragi Dosa' => ['கேழ்வரகு தோசை', 'உடனடி கேழ்வரகு தோசை — மொறுமொறுப்பும் நிறைவும்.'],
        'Masala Oats' => ['மசாலா ஓட்ஸ்', 'காய்கறிகளுடன் காரமான ஓட்ஸ் — விரைவானது, இலகுவானது.'],
        'Keerai Kootu' => ['கீரைக் கூட்டு', 'கீரையும் பாசிப் பருப்பும் சேர்த்த மிதமான கூட்டு.'],
        'Pepper Chicken' => ['மிளகுக் கோழி', 'மிளகு, கறிவேப்பிலையுடன் வறுத்த செட்டிநாடு கோழி.'],
        'Paneer Tikka' => ['பன்னீர் டிக்கா', 'மசாலா தயிரில் ஊறிய பன்னீர், குடைமிளகாய் — கடாயில் வாட்டியது.'],
        'Fish Curry' => ['மீன் குழம்பு', 'தக்காளி அடிப்படையிலான புளிப்பான மீன் குழம்பு.'],
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
