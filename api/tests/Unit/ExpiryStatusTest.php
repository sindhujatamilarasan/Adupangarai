<?php

namespace Tests\Unit;

use App\Support\ExpiryStatus;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ExpiryStatusTest extends TestCase
{
    public function test_status_boundaries(): void
    {
        $today = Carbon::parse('2026-01-10');

        $this->assertNull(ExpiryStatus::for(null, $today));
        $this->assertSame('expired', ExpiryStatus::for(Carbon::parse('2026-01-09'), $today));
        $this->assertSame('expiring_soon', ExpiryStatus::for(Carbon::parse('2026-01-10'), $today));
        $this->assertSame('expiring_soon', ExpiryStatus::for(Carbon::parse('2026-01-13'), $today));
        $this->assertSame('fresh', ExpiryStatus::for(Carbon::parse('2026-01-14'), $today));
    }

    public function test_days_left_ignores_time_of_day(): void
    {
        $this->assertSame(1, ExpiryStatus::daysLeft(Carbon::parse('2026-01-11 00:00'), Carbon::parse('2026-01-10 23:59')));
        $this->assertSame(-2, ExpiryStatus::daysLeft(Carbon::parse('2026-01-08'), Carbon::parse('2026-01-10 08:00')));
    }
}
