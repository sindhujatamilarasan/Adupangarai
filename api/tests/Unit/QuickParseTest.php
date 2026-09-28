<?php

namespace Tests\Unit;

use App\Support\QuickParse;
use PHPUnit\Framework\TestCase;

class QuickParseTest extends TestCase
{
    public function test_reads_common_spoken_lists(): void
    {
        $this->assertSame([
            ['name' => 'chicken', 'quantity' => 1.0, 'unit' => 'kg'],
            ['name' => 'eggs', 'quantity' => 1, 'unit' => 'dozen'],
            ['name' => 'milk', 'quantity' => 2.0, 'unit' => 'litres'],
            ['name' => 'paneer', 'quantity' => 0.5, 'unit' => 'kilo'],
            ['name' => 'eggs', 'quantity' => 12.0, 'unit' => null],
            ['name' => 'basmati rice', 'quantity' => 1.5, 'unit' => 'kg'],
        ], QuickParse::items('I bought 1 kg chicken, a dozen eggs, 2 litres of milk and half kilo paneer; 12 eggs plus 1.5kg basmati rice.'));
    }

    public function test_gives_up_on_free_form_text(): void
    {
        $this->assertNull(QuickParse::items('some chicken and whatever milk was left'));
        $this->assertNull(QuickParse::items(''));
    }
}
