<?php

namespace App\Support;

use App\Models\GroceryItem;
use Illuminate\Support\Collection;

/**
 * Up to three money-saving tips for what's still to buy. Each tip is an English template the app
 * translates, with its numbers and names as params.
 */
class SavingTips
{
    public const MAX = 3;

    /** @param  Collection<int, GroceryItem>  $items  unpurchased items, ingredient.category loaded */
    public static function for(Collection $items): array
    {
        $costs = $items->map(fn ($i) => ['item' => $i, 'cost' => Cost::forItem($i)]);
        $total = $costs->sum('cost');
        $category = fn ($i) => $i->ingredient?->category?->name;
        $tips = [];

        // One item eats a big share of the list.
        $top = $costs->sortByDesc('cost')->first();
        if ($top && $total >= 200 && $top['cost'] >= 0.25 * $total) {
            $tips[] = self::tip('biggest', '{name} is ₹{cost} of your ₹{total} list — the biggest cost this week.', [
                'name' => $top['item']->label, 'cost' => (int) round($top['cost']), 'total' => (int) round($total),
            ]);
        }

        // Meat is the usual budget breaker; one veg day helps most.
        $meat = $costs->filter(fn ($c) => $category($c['item']) === 'Meat & Seafood')->sum('cost');
        if ($meat >= 150) {
            $tips[] = self::tip('meat', 'Meat costs ₹{n} this week. One veg day with sambar or egg curry saves a good amount.', ['n' => (int) round($meat)]);
        }

        // Whole packets bought for a pinch: reassure that the rest isn't wasted.
        $small = $items->filter(fn ($i) => $i->packs && $i->need_quantity && $i->quantity && $i->need_quantity < 0.3 * $i->quantity)->count();
        if ($small >= 2) {
            $tips[] = self::tip('leftover', '{n} items are needed only in small amounts. The rest stays in your kitchen, so you won’t buy them again next week.', ['n' => $small]);
        }

        if ($items->filter(fn ($i) => $category($i) === 'Vegetables')->count() >= 3) {
            $tips[] = self::tip('market', 'Buy vegetables at the uzhavar sandhai or weekly market — usually cheaper and fresher.');
        }

        return array_slice($tips, 0, self::MAX);
    }

    private static function tip(string $key, string $text, array $params = []): array
    {
        return ['key' => $key, 'text' => $text, 'params' => $params];
    }
}
