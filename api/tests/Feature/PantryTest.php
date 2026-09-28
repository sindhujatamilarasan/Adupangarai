<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use App\Support\PantryLedger;
use App\Support\TransactionType;
use App\Support\Unit;
use Database\Seeders\IngredientSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PantryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IngredientSeeder::class);
        $this->user = User::factory()->create();
    }

    private function ingredient(string $name): Ingredient
    {
        return Ingredient::where('name', $name)->firstOrFail();
    }

    private function addToPantry(string $name, float $qty, string $unit, array $extra = []): array
    {
        return $this->actingAs($this->user)->postJson('/api/pantry', [
            'ingredient_id' => $this->ingredient($name)->id, 'quantity' => $qty, 'unit' => $unit, ...$extra,
        ])->assertSuccessful()->json('data');
    }

    public function test_adding_stock_creates_item_and_add_transaction(): void
    {
        $item = $this->addToPantry('Chicken', 500, 'g', ['storage_location' => 'freezer']);

        $this->assertSame(500, $item['quantity'] + 0);
        $this->assertDatabaseHas('pantry_transactions', [
            'pantry_item_id' => $item['id'], 'type' => 'ADD', 'quantity_change' => 500, 'balance_after' => 500,
        ]);
    }

    public function test_adding_same_ingredient_merges_with_unit_conversion(): void
    {
        $this->addToPantry('Chicken', 500, 'g');
        $item = $this->addToPantry('Chicken', 1, 'kg');

        $this->assertEquals(1500, $item['quantity']);
        $this->assertSame('g', $item['unit']);
        $this->assertSame(1, PantryItem::count());
    }

    public function test_adding_incompatible_unit_is_rejected(): void
    {
        $this->addToPantry('Chicken', 500, 'g');

        $this->actingAs($this->user)->postJson('/api/pantry', [
            'ingredient_id' => $this->ingredient('Chicken')->id, 'quantity' => 2, 'unit' => 'piece',
        ])->assertJsonValidationErrors('unit');

        $this->assertEquals(500, PantryItem::first()->quantity);
    }

    public function test_merge_keeps_earliest_expiry(): void
    {
        $this->addToPantry('Milk', 500, 'ml', ['expiry_date' => now()->addDays(5)->toDateString()]);
        $item = $this->addToPantry('Milk', 1, 'L', ['expiry_date' => now()->addDays(2)->toDateString()]);
        $this->assertSame(now()->addDays(2)->toDateString(), $item['expiry_date']);

        $item = $this->addToPantry('Milk', 1, 'L', ['expiry_date' => now()->addDays(9)->toDateString()]);
        $this->assertSame(now()->addDays(2)->toDateString(), $item['expiry_date']);
    }

    public function test_pantry_cannot_become_negative(): void
    {
        $item = PantryItem::find($this->addToPantry('Egg', 4, 'piece')['id']);

        $this->actingAs($this->user)->postJson("/api/pantry/{$item->id}/adjust", ['type' => 'DISCARDED', 'quantity' => 5])
            ->assertJsonValidationErrors('quantity');

        $this->expectException(ValidationException::class);
        try {
            PantryLedger::change($item, -5, Unit::PIECE, TransactionType::COOKED);
        } finally {
            $this->assertEquals(4, $item->fresh()->quantity);
            $this->assertSame(1, $item->transactions()->count());
        }
    }

    public function test_removing_exact_stock_reaches_zero(): void
    {
        $item = PantryItem::find($this->addToPantry('Chicken', 1, 'kg')['id']);

        PantryLedger::change($item, -1000, Unit::G, TransactionType::COOKED);

        $this->assertEquals(0, $item->fresh()->quantity);
    }

    public function test_database_rejects_negative_quantity_directly(): void
    {
        $this->expectException(QueryException::class);
        PantryItem::create([
            'household_id' => $this->user->household_id, 'ingredient_id' => $this->ingredient('Egg')->id,
            'quantity' => -1, 'unit' => 'piece',
        ]);
    }

    public function test_adjust_types_update_quantity_and_history(): void
    {
        $id = $this->addToPantry('Rice', 2, 'kg')['id'];
        $adjust = fn ($body) => $this->actingAs($this->user)->postJson("/api/pantry/$id/adjust", $body)->assertOk();

        $adjust(['type' => 'ADD', 'quantity' => 500, 'unit' => 'g']);
        $adjust(['type' => 'DISCARDED', 'quantity' => 0.5, 'unit' => 'kg']);
        $adjust(['type' => 'ADJUSTMENT', 'quantity' => 1.2, 'note' => 'stock take']); // item is stored in kg

        $this->assertEquals(1.2, PantryItem::find($id)->quantity);
        $history = $this->actingAs($this->user)->getJson("/api/pantry/$id")->json('transactions');
        $this->assertSame(['ADJUSTMENT', 'DISCARDED', 'ADD', 'ADD'], array_column($history, 'type'));
        $this->assertEquals(-0.8, $history[0]['quantity_change']);
    }

    public function test_purchase_and_cooked_are_not_manual_adjust_types(): void
    {
        $id = $this->addToPantry('Rice', 2, 'kg')['id'];

        $this->actingAs($this->user)->postJson("/api/pantry/$id/adjust", ['type' => 'COOKED', 'quantity' => 1])
            ->assertJsonValidationErrors('type');
    }

    public function test_views_filter_and_count(): void
    {
        $this->addToPantry('Egg', 2, 'piece', ['minimum_stock' => 6]);
        $this->addToPantry('Milk', 1, 'L', ['expiry_date' => now()->addDay()->toDateString()]);
        $this->addToPantry('Curd', 200, 'g', ['expiry_date' => now()->subDay()->toDateString()]);
        $this->addToPantry('Rice', 5, 'kg', ['minimum_stock' => 1, 'expiry_date' => now()->addMonths(6)->toDateString()]);

        $res = $this->actingAs($this->user)->getJson('/api/pantry')->assertOk();
        $this->assertSame(['all' => 4, 'low_stock' => 1, 'expiring_soon' => 1, 'expired' => 1], $res->json('counts'));

        $names = fn ($view) => array_column(array_column($this->getJson("/api/pantry?view=$view")->json('data'), 'ingredient'), 'name');
        $this->assertSame(['Egg'], $names('low_stock'));
        $this->assertSame(['Milk'], $names('expiring_soon'));
        $this->assertSame(['Curd'], $names('expired'));

        $milk = collect($res->json('data'))->firstWhere('ingredient.name', 'Milk');
        $this->assertSame('expiring_soon', $milk['expiry_status']);
        $this->assertSame(1, $milk['days_to_expiry']);
    }

    public function test_users_cannot_access_another_households_pantry(): void
    {
        $id = $this->addToPantry('Chicken', 500, 'g')['id'];
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->getJson('/api/pantry')->assertJsonCount(0, 'data');
        $this->actingAs($intruder)->getJson("/api/pantry/$id")->assertNotFound();
        $this->actingAs($intruder)->patchJson("/api/pantry/$id", ['minimum_stock' => 1])->assertNotFound();
        $this->actingAs($intruder)->postJson("/api/pantry/$id/adjust", ['type' => 'DISCARDED', 'quantity' => 500])->assertNotFound();
        $this->actingAs($intruder)->deleteJson("/api/pantry/$id")->assertNotFound();

        $this->assertEquals(500, PantryItem::find($id)->quantity);
    }

    public function test_same_ingredient_in_two_households_stays_separate(): void
    {
        $this->addToPantry('Egg', 4, 'piece');
        $other = User::factory()->create();
        $this->actingAs($other)->postJson('/api/pantry', [
            'ingredient_id' => $this->ingredient('Egg')->id, 'quantity' => 10, 'unit' => 'piece',
        ])->assertCreated();

        $this->assertEquals(4, PantryItem::where('household_id', $this->user->household_id)->first()->quantity);
        $this->assertEquals(10, PantryItem::where('household_id', $other->household_id)->first()->quantity);
    }

    public function test_update_edits_details_but_not_quantity(): void
    {
        $id = $this->addToPantry('Paneer', 200, 'g')['id'];

        $this->actingAs($this->user)->patchJson("/api/pantry/$id", [
            'quantity' => 9999, 'storage_location' => 'fridge', 'minimum_stock' => 100,
        ])->assertOk()->assertJsonPath('data.storage_location', 'fridge');

        $this->assertEquals(200, PantryItem::find($id)->quantity);
    }
}
