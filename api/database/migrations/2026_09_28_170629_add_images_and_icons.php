<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', fn (Blueprint $t) => $t->string('image_path')->nullable());
        Schema::table('ingredients', fn (Blueprint $t) => $t->string('icon', 16)->nullable());
        Schema::table('ingredient_categories', fn (Blueprint $t) => $t->string('icon', 16)->nullable());
    }

    public function down(): void
    {
        Schema::table('recipes', fn (Blueprint $t) => $t->dropColumn('image_path'));
        Schema::table('ingredients', fn (Blueprint $t) => $t->dropColumn('icon'));
        Schema::table('ingredient_categories', fn (Blueprint $t) => $t->dropColumn('icon'));
    }
};
