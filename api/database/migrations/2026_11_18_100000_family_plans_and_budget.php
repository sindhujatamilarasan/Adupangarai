<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Family meal planning:
 * - recipe tags: lunchbox (packs well, fine cold at noon), kids (mild, kid-friendly), prep_ahead (make on Sunday, keeps);
 * - a usual shop price per ingredient pack, so plans and grocery lists can show an estimated cost;
 * - a weekly grocery budget per kitchen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', fn (Blueprint $t) => $t->json('tags')->nullable());
        Schema::table('ingredients', fn (Blueprint $t) => $t->decimal('pack_price', 8, 2)->nullable());
        Schema::table('households', fn (Blueprint $t) => $t->unsignedInteger('weekly_budget')->nullable());
    }

    public function down(): void
    {
        Schema::table('households', fn (Blueprint $t) => $t->dropColumn('weekly_budget'));
        Schema::table('ingredients', fn (Blueprint $t) => $t->dropColumn('pack_price'));
        Schema::table('recipes', fn (Blueprint $t) => $t->dropColumn('tags'));
    }
};
