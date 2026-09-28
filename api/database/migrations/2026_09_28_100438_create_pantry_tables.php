<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pantry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('unit', 10);
            $table->date('expiry_date')->nullable();
            $table->decimal('minimum_stock', 12, 3)->nullable();
            $table->string('storage_location', 20)->nullable();
            $table->timestamps();

            $table->unique(['household_id', 'ingredient_id']);
            $table->index(['household_id', 'expiry_date']);
        });

        DB::statement('ALTER TABLE pantry_items ADD CONSTRAINT pantry_items_quantity_non_negative CHECK (quantity >= 0)');

        Schema::create('pantry_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pantry_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('quantity_change', 12, 3);
            $table->string('unit', 10);
            $table->decimal('balance_after', 12, 3);
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['household_id', 'created_at']);
            $table->index('pantry_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pantry_transactions');
        Schema::dropIfExists('pantry_items');
    }
};
