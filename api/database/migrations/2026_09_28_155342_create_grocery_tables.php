<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One running list per household.
        Schema::create('grocery_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('planned_from')->nullable();
            $table->date('planned_to')->nullable();
            $table->timestamps();
        });

        Schema::create('grocery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grocery_list_id')->constrained()->cascadeOnDelete();
            // Null for non-food/manual items such as soap.
            $table->foreignId('ingredient_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('category', 50);
            $table->decimal('quantity', 12, 3)->nullable();
            $table->string('unit', 10)->nullable();
            $table->string('source', 10); // plan | manual
            $table->boolean('purchased')->default(false);
            $table->decimal('actual_quantity', 12, 3)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamp('added_to_pantry_at')->nullable();
            $table->timestamps();

            $table->index(['grocery_list_id', 'purchased']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_items');
        Schema::dropIfExists('grocery_lists');
    }
};
