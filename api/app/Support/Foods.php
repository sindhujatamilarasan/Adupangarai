<?php

namespace App\Support;

/**
 * Built-in calories for common Indian foods and drinks, so logging "2 vada and a coffee" needs no AI.
 * Values are per typical home/street serving (Indian Food Composition Tables, rounded).
 * Each entry: [name, Tamil name, kcal, protein g, aliases (lower case, English / Tanglish / Tamil)].
 */
class Foods
{
    public const LIST = [
        // Tiffin
        ['Idli (1)', 'இட்லி (1)', 40, 1.5, ['idli', 'idly', 'இட்லி']],
        ['Dosa (1)', 'தோசை (1)', 135, 3, ['dosa', 'dosai', 'thosai', 'தோசை']],
        ['Masala dosa (1)', 'மசாலா தோசை (1)', 250, 5, ['masala dosa', 'மசாலா தோசை']],
        ['Ghee roast (1)', 'நெய் ரோஸ்ட் (1)', 280, 4, ['ghee roast', 'roast', 'நெய் ரோஸ்ட்']],
        ['Uttapam (1)', 'ஊத்தப்பம் (1)', 200, 5, ['uttapam', 'oothappam', 'ஊத்தப்பம்']],
        ['Rava dosa (1)', 'ரவா தோசை (1)', 180, 3, ['rava dosa', 'ரவா தோசை']],
        ['Appam (1)', 'ஆப்பம் (1)', 120, 2, ['appam', 'ஆப்பம்']],
        ['Idiyappam (1)', 'இடியாப்பம் (1)', 45, 1, ['idiyappam', 'string hopper', 'இடியாப்பம்']],
        ['Puttu (1 cup)', 'புட்டு (1 கப்)', 200, 4, ['puttu', 'புட்டு']],
        ['Pongal (1 cup)', 'பொங்கல் (1 கப்)', 300, 8, ['pongal', 'ven pongal', 'பொங்கல்']],
        ['Upma (1 cup)', 'உப்புமா (1 கப்)', 250, 6, ['upma', 'uppuma', 'உப்புமா']],
        ['Poori (1)', 'பூரி (1)', 100, 2, ['poori', 'puri', 'பூரி']],
        ['Chapati (1)', 'சப்பாத்தி (1)', 100, 3, ['chapati', 'chapathi', 'roti', 'சப்பாத்தி']],
        ['Parotta (1)', 'பரோட்டா (1)', 260, 5, ['parotta', 'porotta', 'paratha', 'பரோட்டா']],
        ['Pesarattu (1)', 'பெசரட்டு (1)', 150, 7, ['pesarattu', 'பெசரட்டு']],
        ['Adai (1)', 'அடை (1)', 220, 8, ['adai', 'அடை']],
        ['Aval / poha (1 cup)', 'அவல் (1 கப்)', 250, 5, ['poha', 'aval', 'அவல்']],
        // Sides
        ['Sambar (1 cup)', 'சாம்பார் (1 கப்)', 130, 6, ['sambar', 'sambhar', 'சாம்பார்']],
        ['Coconut chutney (2 tbsp)', 'தேங்காய் சட்னி (2 ஸ்பூன்)', 70, 1, ['chutney', 'coconut chutney', 'சட்னி']],
        ['Rasam (1 cup)', 'ரசம் (1 கப்)', 60, 2, ['rasam', 'ரசம்']],
        ['Kootu (1 cup)', 'கூட்டு (1 கப்)', 150, 6, ['kootu', 'கூட்டு']],
        ['Poriyal (1 cup)', 'பொரியல் (1 கப்)', 120, 3, ['poriyal', 'பொரியல்']],
        ['Dal (1 cup)', 'பருப்பு (1 கப்)', 180, 9, ['dal', 'dhal', 'paruppu', 'பருப்பு']],
        ['Curd (1 cup)', 'தயிர் (1 கப்)', 100, 6, ['curd', 'yogurt', 'thayir', 'தயிர்']],
        ['Buttermilk (1 glass)', 'மோர் (1 கிளாஸ்)', 40, 2, ['buttermilk', 'mor', 'moru', 'மோர்']],
        ['Pickle (1 tsp)', 'ஊறுகாய் (1 ஸ்பூன்)', 30, 0, ['pickle', 'oorugai', 'ஊறுகாய்']],
        ['Appalam (1)', 'அப்பளம் (1)', 60, 2, ['appalam', 'papad', 'அப்பளம்']],
        // Rice and meals
        ['Rice (1 cup)', 'சாதம் (1 கப்)', 200, 4, ['rice', 'sadam', 'saadham', 'சாதம்']],
        ['Curd rice (1 cup)', 'தயிர் சாதம் (1 கப்)', 220, 6, ['curd rice', 'thayir sadam', 'தயிர் சாதம்']],
        ['Lemon rice (1 cup)', 'எலுமிச்சை சாதம் (1 கப்)', 250, 4, ['lemon rice', 'எலுமிச்சை சாதம்']],
        ['Tamarind rice (1 cup)', 'புளி சாதம் (1 கப்)', 280, 4, ['tamarind rice', 'puliyodharai', 'puli sadam', 'புளியோதரை']],
        ['Sambar rice (1 cup)', 'சாம்பார் சாதம் (1 கப்)', 260, 7, ['sambar rice', 'sambar sadam', 'சாம்பார் சாதம்']],
        ['Veg biryani (1 plate)', 'வெஜ் பிரியாணி (1 பிளேட்)', 450, 9, ['veg biryani', 'வெஜ் பிரியாணி']],
        ['Chicken biryani (1 plate)', 'சிக்கன் பிரியாணி (1 பிளேட்)', 600, 25, ['biryani', 'chicken biryani', 'பிரியாணி', 'சிக்கன் பிரியாணி']],
        ['Mutton biryani (1 plate)', 'மட்டன் பிரியாணி (1 பிளேட்)', 700, 28, ['mutton biryani', 'மட்டன் பிரியாணி']],
        ['Fried rice (1 plate)', 'ஃப்ரைடு ரைஸ் (1 பிளேட்)', 450, 10, ['fried rice', 'ஃப்ரைடு ரைஸ்']],
        ['South Indian meals (1 plate)', 'சாப்பாடு மீல்ஸ் (1 பிளேட்)', 700, 18, ['meals', 'full meals', 'மீல்ஸ்']],
        // Non-veg
        ['Boiled egg (1)', 'அவிச்ச முட்டை (1)', 78, 6, ['egg', 'boiled egg', 'muttai', 'முட்டை']],
        ['Omelette (1 egg)', 'ஆம்லெட் (1 முட்டை)', 110, 6, ['omelette', 'omelet', 'ஆம்லெட்']],
        ['Chicken curry (1 cup)', 'சிக்கன் குழம்பு (1 கப்)', 300, 25, ['chicken curry', 'chicken kuzhambu', 'சிக்கன் குழம்பு']],
        ['Chicken 65 (6 pieces)', 'சிக்கன் 65 (6 பீஸ்)', 350, 22, ['chicken 65', 'சிக்கன் 65']],
        ['Fish fry (1 piece)', 'மீன் வறுவல் (1 பீஸ்)', 200, 18, ['fish fry', 'meen varuval', 'மீன் வறுவல்']],
        ['Fish curry (1 cup)', 'மீன் குழம்பு (1 கப்)', 220, 20, ['fish curry', 'meen kuzhambu', 'மீன் குழம்பு']],
        ['Mutton curry (1 cup)', 'மட்டன் குழம்பு (1 கப்)', 350, 24, ['mutton curry', 'மட்டன் குழம்பு']],
        ['Paneer butter masala (1 cup)', 'பனீர் பட்டர் மசாலா (1 கப்)', 350, 14, ['paneer butter masala', 'paneer', 'பனீர்']],
        // Snacks
        ['Vada (1)', 'வடை (1)', 140, 4, ['vada', 'vadai', 'medu vada', 'வடை']],
        ['Bajji (2)', 'பஜ்ஜி (2)', 180, 3, ['bajji', 'bhajji', 'பஜ்ஜி']],
        ['Bonda (1)', 'போண்டா (1)', 150, 3, ['bonda', 'போண்டா']],
        ['Samosa (1)', 'சமோசா (1)', 250, 4, ['samosa', 'சமோசா']],
        ['Pakoda (1 plate)', 'பக்கோடா (1 பிளேட்)', 300, 6, ['pakoda', 'pakora', 'பக்கோடா']],
        ['Murukku (handful)', 'முறுக்கு (ஒரு கைப்பிடி)', 150, 2, ['murukku', 'முறுக்கு']],
        ['Mixture (handful)', 'மிக்சர் (ஒரு கைப்பிடி)', 160, 4, ['mixture', 'மிக்சர்']],
        ['Biscuits (2)', 'பிஸ்கட் (2)', 90, 1, ['biscuit', 'biscuits', 'பிஸ்கட்']],
        ['Puffs (1)', 'பஃப்ஸ் (1)', 280, 5, ['puffs', 'puff', 'பஃப்ஸ்']],
        ['Sundal (1 cup)', 'சுண்டல் (1 கப்)', 180, 9, ['sundal', 'சுண்டல்']],
        ['Peanuts (handful)', 'வேர்க்கடலை (ஒரு கைப்பிடி)', 170, 7, ['peanuts', 'groundnut', 'kadalai', 'வேர்க்கடலை']],
        ['Nuts (handful)', 'நட்ஸ் (ஒரு கைப்பிடி)', 170, 5, ['nuts', 'almonds', 'cashew', 'நட்ஸ்', 'பாதாம்']],
        ['Chips (small pack)', 'சிப்ஸ் (சின்ன பாக்கெட்)', 160, 2, ['chips', 'சிப்ஸ்']],
        ['Bread slice (1)', 'பிரெட் (1 ஸ்லைஸ்)', 75, 2.5, ['bread', 'பிரெட்']],
        // Sweets
        ['Sweet (1 piece)', 'ஸ்வீட் (1 பீஸ்)', 150, 2, ['sweet', 'ஸ்வீட்']],
        ['Laddu (1)', 'லட்டு (1)', 180, 3, ['laddu', 'ladoo', 'லட்டு']],
        ['Mysore pak (1)', 'மைசூர் பாக் (1)', 200, 2, ['mysore pak', 'மைசூர் பாக்']],
        ['Jalebi (1)', 'ஜிலேபி (1)', 150, 1, ['jalebi', 'jilebi', 'ஜிலேபி']],
        ['Gulab jamun (1)', 'குலாப் ஜாமூன் (1)', 150, 2, ['gulab jamun', 'jamun', 'குலாப் ஜாமூன்']],
        ['Payasam (1 cup)', 'பாயசம் (1 கப்)', 250, 5, ['payasam', 'kheer', 'பாயசம்']],
        ['Kesari (1 cup)', 'கேசரி (1 கப்)', 300, 3, ['kesari', 'கேசரி']],
        ['Ice cream (1 scoop)', 'ஐஸ்கிரீம் (1 ஸ்கூப்)', 140, 2, ['ice cream', 'ஐஸ்கிரீம்']],
        ['Chocolate (small bar)', 'சாக்லேட் (சின்ன பார்)', 150, 2, ['chocolate', 'சாக்லேட்']],
        // Fruit
        ['Banana (1)', 'வாழைப்பழம் (1)', 105, 1, ['banana', 'vazhaipazham', 'வாழைப்பழம்']],
        ['Apple (1)', 'ஆப்பிள் (1)', 95, 0.5, ['apple', 'ஆப்பிள்']],
        ['Orange (1)', 'ஆரஞ்சு (1)', 60, 1, ['orange', 'ஆரஞ்சு']],
        ['Guava (1)', 'கொய்யா (1)', 70, 2.5, ['guava', 'koyya', 'கொய்யா']],
        ['Mango (1)', 'மாம்பழம் (1)', 200, 3, ['mango', 'மாம்பழம்']],
        ['Papaya (1 cup)', 'பப்பாளி (1 கப்)', 60, 1, ['papaya', 'பப்பாளி']],
        ['Watermelon (1 cup)', 'தர்பூசணி (1 கப்)', 45, 1, ['watermelon', 'தர்பூசணி']],
        ['Grapes (1 cup)', 'திராட்சை (1 கப்)', 100, 1, ['grapes', 'திராட்சை']],
        ['Dates (3)', 'பேரீச்சை (3)', 70, 0.5, ['dates', 'பேரீச்சை']],
        // Drinks
        ['Tea with milk and sugar', 'டீ (பால், சர்க்கரை)', 60, 2, ['tea', 'chai', 'டீ']],
        ['Filter coffee', 'ஃபில்டர் காபி', 80, 2, ['coffee', 'filter coffee', 'kaapi', 'காபி']],
        ['Milk (1 glass)', 'பால் (1 கிளாஸ்)', 150, 8, ['milk', 'paal', 'பால்']],
        ['Badam milk (1 glass)', 'பாதாம் பால் (1 கிளாஸ்)', 220, 8, ['badam milk', 'பாதாம் பால்']],
        ['Fruit juice (1 glass)', 'ஜூஸ் (1 கிளாஸ்)', 120, 1, ['juice', 'fruit juice', 'ஜூஸ்']],
        ['Soft drink (1 glass)', 'கூல் டிரிங்க்ஸ் (1 கிளாஸ்)', 100, 0, ['soft drink', 'cool drink', 'coke', 'pepsi', 'கூல் டிரிங்க்ஸ்']],
        ['Lassi (1 glass)', 'லஸ்ஸி (1 கிளாஸ்)', 200, 7, ['lassi', 'லஸ்ஸி']],
        ['Tender coconut (1)', 'இளநீர் (1)', 45, 0.5, ['tender coconut', 'ilaneer', 'இளநீர்']],
        ['Horlicks / Boost (1 glass)', 'ஹார்லிக்ஸ் / பூஸ்ட் (1 கிளாஸ்)', 180, 7, ['horlicks', 'boost', 'bournvita', 'ஹார்லிக்ஸ்', 'பூஸ்ட்']],
        // Street / restaurant
        ['Pani puri (6)', 'பானி பூரி (6)', 200, 3, ['pani puri', 'golgappa', 'பானி பூரி']],
        ['Bhel puri (1 plate)', 'பேல் பூரி (1 பிளேட்)', 250, 5, ['bhel puri', 'bhel', 'பேல் பூரி']],
        ['Kothu parotta (1 plate)', 'கொத்து பரோட்டா (1 பிளேட்)', 550, 15, ['kothu parotta', 'கொத்து பரோட்டா']],
        ['Noodles (1 plate)', 'நூடுல்ஸ் (1 பிளேட்)', 400, 9, ['noodles', 'maggi', 'நூடுல்ஸ்']],
        ['Pizza (1 slice)', 'பீட்சா (1 ஸ்லைஸ்)', 280, 12, ['pizza', 'பீட்சா']],
        ['Burger (1)', 'பர்கர் (1)', 350, 15, ['burger', 'பர்கர்']],
        ['Shawarma (1)', 'ஷவர்மா (1)', 450, 25, ['shawarma', 'ஷவர்மா']],
    ];

    private const NUMBER_WORDS = [
        'a' => 1, 'an' => 1, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6,
        'half' => 0.5, 'oru' => 1, 'rendu' => 2, 'moonu' => 3, 'naalu' => 4,
        'ஒரு' => 1, 'ரெண்டு' => 2, 'இரண்டு' => 2, 'மூணு' => 3, 'மூன்று' => 3, 'நாலு' => 4,
    ];

    /** Foods matching a search (English or Tamil), for the "find a food" box. */
    public static function search(string $q, int $limit = 12): array
    {
        $q = mb_strtolower(trim($q));
        $rows = $q === '' ? [] : array_filter(self::LIST, fn ($f) => str_contains(mb_strtolower($f[0]), $q)
            || str_contains($f[1], $q) || array_filter($f[4], fn ($a) => str_contains($a, $q)));

        return array_values(array_map(self::row(...), array_slice($rows, 0, $limit)));
    }

    /**
     * "2 vada and a coffee" / "ரெண்டு இட்லி, காபி" -> rows. Null if any part isn't a known food.
     *
     * @return list<array{name: string, calories: float, protein_g: float}>|null
     */
    public static function parse(string $text): ?array
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/^(i\s+)?(just\s+)?(ate|had|have eaten|eaten)\s+/u', '', $text);
        $parts = preg_split('/\s*(?:,|;|\n|\band\b|\bwith\b|\bplus\b|&|மற்றும்)\s*/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! $parts) {
            return null;
        }

        $words = implode('|', array_map('preg_quote', array_keys(self::NUMBER_WORDS)));
        $rows = [];
        foreach ($parts as $part) {
            $part = trim($part, " .!\t");
            $count = 1.0;
            if (preg_match("/^(\\d+(?:\\.\\d+)?|{$words})\\s+(.+)$/u", $part, $m)) {
                $count = is_numeric($m[1]) ? (float) $m[1] : self::NUMBER_WORDS[$m[1]];
                $part = $m[2];
            }
            $food = self::find($part);
            if (! $food || $count <= 0 || $count > 20) {
                return null;
            }
            // Entries that already describe 2 pieces (e.g. "Bajji (2)") count per that serving.
            $per = preg_match('/\((\d+)\)$/', $food[0], $s) ? (int) $s[1] : 1;
            $servings = $per > 1 ? $count / $per : $count;
            $rows[] = [
                'name' => self::label($food).($servings != 1 ? ' × '.rtrim(rtrim(number_format($servings, 1), '0'), '.') : ''),
                'calories' => round($food[2] * $servings),
                'protein_g' => round($food[3] * $servings, 1),
            ];
        }

        return $rows;
    }

    /**
     * Longest alias found in the text wins ("masala dosa" before "dosa"). At most two other words may
     * remain ("hot", "plate of"); anything longer is a description we can't be sure about, so the AI reads it.
     */
    private static function find(string $text): ?array
    {
        $text = preg_replace('/s\b/u', '', $text); // "vadas" -> "vada", "idlis" -> "idli"
        $best = null;
        $bestAlias = '';
        foreach (self::LIST as $food) {
            foreach ($food[4] as $alias) {
                $a = preg_replace('/s\b/u', '', $alias);
                if (mb_strlen($a) > mb_strlen($bestAlias) && preg_match('/(^|\s)'.preg_quote($a, '/').'(\s|$)/u', $text)) {
                    [$best, $bestAlias] = [$food, $a];
                }
            }
        }
        $rest = trim(str_replace($bestAlias, ' ', $text));

        return $best && ($rest === '' || count(preg_split('/\s+/u', $rest)) <= 2) ? $best : null;
    }

    private static function label(array $food): string
    {
        return app()->getLocale() === 'ta' ? $food[1] : $food[0];
    }

    private static function row(array $food): array
    {
        return ['name' => $food[0], 'label' => self::label($food), 'calories' => $food[2], 'protein_g' => $food[3]];
    }
}
