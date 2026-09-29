<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('locale', 5)->default('en'));
        Schema::table('ingredients', fn (Blueprint $t) => $t->string('name_ta')->nullable());
        Schema::table('ingredient_categories', fn (Blueprint $t) => $t->string('name_ta')->nullable());
        Schema::table('recipes', function (Blueprint $t) {
            $t->string('name_ta')->nullable();
            $t->text('description_ta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('locale'));
        Schema::table('ingredients', fn (Blueprint $t) => $t->dropColumn('name_ta'));
        Schema::table('ingredient_categories', fn (Blueprint $t) => $t->dropColumn('name_ta'));
        Schema::table('recipes', fn (Blueprint $t) => $t->dropColumn(['name_ta', 'description_ta']));
    }
};
