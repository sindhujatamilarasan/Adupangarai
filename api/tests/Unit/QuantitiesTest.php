<?php

namespace Tests\Unit;

use App\Support\Quantities;
use PHPUnit\Framework\TestCase;

class QuantitiesTest extends TestCase
{
    public function test_two_serving_recipe_scaled_to_four_doubles(): void
    {
        $this->assertSame(400.0, Quantities::scale(200, 2, 4));
        $this->assertSame(6.0, Quantities::scale(3, 2, 4));
    }

    public function test_scaling_down_and_uneven_ratios(): void
    {
        $this->assertSame(125.0, Quantities::scale(500, 4, 1));
        $this->assertSame(0.667, Quantities::scale(1, 3, 2));
    }

    public function test_rejects_zero_servings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Quantities::scale(1, 0, 2);
    }
}
