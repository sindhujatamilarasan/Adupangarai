<?php

namespace Tests\Feature;

use App\Models\MealPlan;
use App\Models\PantryItem;
use App\Models\PantryTransaction;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_seed_is_repeatable_and_gives_a_usable_demo(): void
    {
        $this->seed();
        $this->seed();

        $demo = User::where('email', 'demo@adupangarai.test')->firstOrFail();
        $this->assertGreaterThanOrEqual(10, Recipe::whereNull('household_id')->count());
        $this->assertSame(33, PantryItem::where('household_id', $demo->household_id)->count());
        $this->assertSame(33, PantryTransaction::where('household_id', $demo->household_id)->count());
        $this->assertSame(13, MealPlan::where('household_id', $demo->household_id)->count());

        $d = $this->actingAs($demo)->getJson('/api/dashboard')->assertOk()->json();
        $this->assertNotEmpty($d['cook_now']);
        $this->assertNotEmpty($d['use_soon']);
        $this->assertSame(1, $d['expired_count']);
    }
}
