<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Personal health coach: one goal per person, plus daily weight, steps and food logs. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('sex', 10);
            $table->unsignedSmallInteger('birth_year');
            $table->decimal('height_cm', 5, 1);
            $table->decimal('start_weight_kg', 5, 1);
            $table->decimal('target_weight_kg', 5, 1);
            $table->string('activity', 20);
            $table->string('goal', 10);
            $table->decimal('pace_kg', 3, 2);
            $table->unsignedInteger('step_goal');
            $table->date('started_on');
            $table->timestamps();
        });

        Schema::create('weight_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('weight_kg', 5, 1);
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        Schema::create('step_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('steps');
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        Schema::create('food_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // Set when the food is a planned meal ("I ate it"); name and numbers are copied so history survives plan changes.
            $table->foreignId('meal_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->decimal('portion', 3, 1)->default(1);
            $table->decimal('calories', 6, 1);
            $table->decimal('protein_g', 5, 1)->default(0);
            $table->string('source', 10);
            $table->timestamps();
            $table->index(['user_id', 'date']);
            $table->unique(['user_id', 'meal_plan_id']);
        });

        DB::statement('ALTER TABLE food_logs ADD CONSTRAINT food_logs_values_ok CHECK (calories >= 0 AND protein_g >= 0 AND portion > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('food_logs');
        Schema::dropIfExists('step_logs');
        Schema::dropIfExists('weight_logs');
        Schema::dropIfExists('health_profiles');
    }
};
