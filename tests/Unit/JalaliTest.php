<?php

namespace Tests\Unit;

use App\Support\Jalali;
use Carbon\Carbon;
use Tests\TestCase;

class JalaliTest extends TestCase
{
    public function test_it_displays_utc_timestamps_in_tehran_time(): void
    {
        $utc = Carbon::create(2026, 7, 26, 12, 30, 0, 'UTC');

        $this->assertStringEndsWith('۱۶:۰۰', Jalali::formatNumeric($utc));
        $this->assertSame('UTC', $utc->timezoneName);
        $this->assertSame('12:30', $utc->format('H:i'));
    }

    public function test_timezone_conversion_happens_before_jalali_date_conversion(): void
    {
        $utc = Carbon::create(2026, 7, 26, 21, 30, 0, 'UTC');

        $this->assertSame('۱۴۰۵/۰۵/۰۵  ۰۱:۰۰', Jalali::formatNumeric($utc));
    }
}
