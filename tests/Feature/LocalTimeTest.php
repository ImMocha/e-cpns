<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class LocalTimeTest extends TestCase
{
    public function test_times_default_to_wib_and_keep_the_original_instant(): void
    {
        $date = Carbon::parse('2026-10-25 17:05:00', 'UTC');

        $view = $this->blade('<x-local-time :value="$date" />', ['date' => $date]);

        $view->assertSee('26 Okt 2026 00:05 WIB')
            ->assertSee('datetime="2026-10-26T00:05:00+07:00"', false);
        $this->assertSame('2026-10-25 17:05:00 UTC', $date->format('Y-m-d H:i:s T'));
    }

    public function test_date_only_displays_use_the_wib_calendar_date(): void
    {
        $view = $this->blade('<x-local-time :value="$date" format="l, d F Y" />', [
            'date' => Carbon::parse('2026-10-25 17:05:00', 'UTC'),
        ]);

        $view->assertSee('Senin, 26 Oktober 2026')->assertDontSee('WIB');
    }

    public function test_missing_dates_show_an_escaped_fallback_without_a_timestamp(): void
    {
        $view = $this->blade('<x-local-time :value="null" :fallback="$fallback" />', [
            'fallback' => '<b>Tanpa batas waktu</b>',
        ]);

        $view->assertSee('&lt;b&gt;Tanpa batas waktu&lt;/b&gt;', false)->assertDontSee('<time', false);
    }
}
