<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How things are sold. A recipe needs "2 tsp turmeric", but the shop sells a 100 g packet:
 * ingredients get a usual pack size (stored in the ingredient's own unit, plus a label like "100 g"),
 * and grocery items remember both what the plan needs and what to buy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $t) {
            $t->decimal('pack_quantity', 10, 3)->nullable(); // in default_unit, e.g. 33 (tsp) for a 100 g turmeric packet
            $t->string('pack_label', 30)->nullable();        // what the shop calls it, e.g. "100 g"
        });
        Schema::table('grocery_items', function (Blueprint $t) {
            $t->decimal('need_quantity', 12, 3)->nullable(); // what the plan needs (same unit as quantity)
            $t->unsignedSmallInteger('packs')->nullable();   // quantity = packs x the ingredient's pack
        });
    }

    public function down(): void
    {
        Schema::table('grocery_items', fn (Blueprint $t) => $t->dropColumn(['need_quantity', 'packs']));
        Schema::table('ingredients', fn (Blueprint $t) => $t->dropColumn(['pack_quantity', 'pack_label']));
    }
};
