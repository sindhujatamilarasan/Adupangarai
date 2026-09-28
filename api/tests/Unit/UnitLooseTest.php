<?php

namespace Tests\Unit;

use App\Support\Unit;
use PHPUnit\Framework\TestCase;

class UnitLooseTest extends TestCase
{
    public function test_spoken_unit_words(): void
    {
        $this->assertSame([Unit::KG, 1], Unit::fromLoose('Kilos'));
        $this->assertSame([Unit::L, 1], Unit::fromLoose('litres'));
        $this->assertSame([Unit::G, 1], Unit::fromLoose('gms'));
        $this->assertSame([Unit::TBSP, 1], Unit::fromLoose('tablespoons'));
        $this->assertSame([Unit::PIECE, 12], Unit::fromLoose('dozen'));
        $this->assertSame([Unit::PIECE, 1], Unit::fromLoose(null));
        $this->assertNull(Unit::fromLoose('handful'));
    }
}
