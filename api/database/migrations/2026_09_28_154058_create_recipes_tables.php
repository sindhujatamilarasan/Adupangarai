<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            // Null = built-in recipe visible to every household (read-only).
            $table->foreignId('household_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('meal_type', 20);
            $table->string('cuisine', 50)->nullable();
            $table->unsignedSmallInteger('servings');
            $table->unsignedSmallInteger('prep_time')->default(0);
            $table->unsignedSmallInteger('cook_time')->default(0);
            $table->boolean('is_veg')->default(true);
            $table->timestamps();

            $table->index(['household_id', 'meal_type']);
        });

        DB::statement('ALTER TABLE recipes ADD CONSTRAINT recipes_servings_positive CHECK (servings > 0)');

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('unit', 10);
            $table->boolean('optional')->default(false);

            $table->unique(['recipe_id', 'ingredient_id']);
            $table->index('ingredient_id');
        });

        DB::statement('ALTER TABLE recipe_ingredients ADD CONSTRAINT recipe_ingredients_quantity_positive CHECK (quantity > 0)');

        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('text');

            $table->unique(['recipe_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
    }
};
