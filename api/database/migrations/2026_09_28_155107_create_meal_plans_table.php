<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('meal_type', 20);
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('servings');
            $table->timestamp('cooked_at')->nullable();
            $table->timestamps();

            $table->index(['household_id', 'date']);
        });

        DB::statement('ALTER TABLE meal_plans ADD CONSTRAINT meal_plans_servings_positive CHECK (servings > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plans');
    }
};
