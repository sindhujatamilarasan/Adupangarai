<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Estimated nutrition per serving.
        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('calories', 7, 1)->nullable();
            $table->decimal('protein_g', 6, 1)->nullable();
            $table->decimal('carbs_g', 6, 1)->nullable();
            $table->decimal('fat_g', 6, 1)->nullable();
            $table->decimal('fiber_g', 6, 1)->nullable();
            $table->timestamp('nutrition_estimated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['calories', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'nutrition_estimated_at']);
        });
    }
};
