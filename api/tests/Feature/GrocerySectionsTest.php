<?php

namespace Tests\Feature;

use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\User;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The list reads like a shop trip (sections in order, bought items last) and gives money-saving tips. */
class GrocerySectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private GroceryList $list;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IngredientSeeder::class);
        $this->user = User::factory()->create();
        $this->list = GroceryList::for($this->user->household_id);
    }

    private function add(string $name, array $extra = []): GroceryItem
    {
        $i = Ingredient::with('category')->where('name', $name)->first();

        return $this->list->items()->create([
            'ingredient_id' => $i?->id, 'name' => $name, 'category' => $i?->category->name ?? 'Household',
            'quantity' => $i?->pack_quantity, 'unit' => $i?->default_unit, 'packs' => $i?->pack_quantity ? 1 : null, 'source' => 'manual',
            ...$extra,
        ]);
    }

    private function tipKeys(): array
    {
        return array_column($this->actingAs($this->user)->getJson('/api/grocery')->json('tips'), 'key');
    }

    public function test_items_come_in_shop_section_order_with_bought_items_last(): void
    {
        $this->add('Soap');
        $this->add('Rice');
        $this->add('Tomato', ['purchased' => true]);
        $this->add('Onion');

        $res = $this->actingAs($this->user)->getJson('/api/grocery')->assertOk();

        $this->assertSame(['Onion', 'Tomato', 'Rice', 'Soap'], array_column($res->json('data'), 'name'));
        $this->assertSame(['Vegetables', 'Vegetables', 'Grains & Flours', 'Household'], array_column($res->json('data'), 'section'));
        $this->assertSame('🥦', $res->json('data.0.section_icon'));
    }

    public function test_sections_are_in_tamil_for_tamil_users(): void
    {
        $this->add('Onion');
        $this->add('Soap');

        $res = $this->actingAs($this->user)->getJson('/api/grocery', ['Accept-Language' => 'ta'])->assertOk();

        $this->assertSame(['காய்கறி', 'வீட்டு சாமான்'], array_column($res->json('data'), 'section'));
    }

    public function test_tips_point_at_the_biggest_cost_and_meat(): void
    {
        $this->add('Chicken', ['packs' => 2, 'quantity' => 1000]);  // 2 × ½ kg ≈ ₹260
        $this->add('Onion');

        $tips = $this->actingAs($this->user)->getJson('/api/grocery')->json('tips');

        $this->assertSame(['biggest', 'meat'], array_column($tips, 'key'));
        $this->assertSame('Chicken', $tips[0]['params']['name']);
        $this->assertGreaterThan(0, $tips[1]['params']['n']);
    }

    public function test_tips_for_small_amounts_and_vegetables(): void
    {
        foreach (['Turmeric Powder', 'Cumin Seeds'] as $name) {
            $this->add($name, ['need_quantity' => 2]);
        }
        foreach (['Onion', 'Tomato', 'Carrot'] as $name) {
            $this->add($name);
        }

        $this->assertSame(['leftover', 'market'], $this->tipKeys());
    }

    public function test_bought_items_give_no_tips(): void
    {
        $this->add('Chicken', ['purchased' => true]);

        $this->assertSame([], $this->tipKeys());
    }
}
