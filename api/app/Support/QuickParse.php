<?php

namespace App\Support;

/**
 * Instant, AI-free reader for simple lists like "1 kg chicken, a dozen eggs and half kilo paneer".
 * Returns null when any part can't be read, so the caller can fall back to the AI.
 */
class QuickParse
{
    private const NUMBER_WORDS = [
        'a' => 1, 'an' => 1, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6,
        'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10, 'eleven' => 11, 'twelve' => 12,
        'half' => 0.5, 'half a' => 0.5, 'quarter' => 0.25, 'a quarter' => 0.25,
    ];

    /** @return list<array{name: string, quantity: float, unit: ?string}>|null */
    public static function items(string $text): ?array
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/^(i\s+)?(just\s+)?(bought|purchased|got|have|added|add|buy)\s+/', '', $text);
        $chunks = preg_split('/\s*(?:,|;|\n|\band\b|\bplus\b)\s*/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! $chunks) {
            return null;
        }

        $numbers = implode('|', array_map('preg_quote', array_keys(self::NUMBER_WORDS)));
        $items = [];
        foreach ($chunks as $chunk) {
            $chunk = trim($chunk, ' .!');
            // [quantity] [unit] [of] name   e.g. "2 litres of milk", "12 eggs", "half kilo paneer", "1.5kg rice"
            if (! preg_match("/^(\\d+(?:\\.\\d+)?|{$numbers})\\s*([a-z]+)?\\s+(?:of\\s+)?(.+)$/", $chunk, $m)) {
                return null;
            }
            $quantity = is_numeric($m[1]) ? (float) $m[1] : self::NUMBER_WORDS[$m[1]];
            $unitWord = $m[2] ?? '';
            $name = trim($m[3]);

            // "12 eggs": the word after the number is the item, not a unit.
            if ($unitWord !== '' && Unit::fromLoose($unitWord) === null) {
                $name = trim($unitWord.' '.$name);
                $unitWord = '';
            }
            $items[] = ['name' => $name, 'quantity' => $quantity, 'unit' => $unitWord ?: null];
        }

        return $items;
    }
}
