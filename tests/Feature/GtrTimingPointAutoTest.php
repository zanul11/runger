<?php

namespace Tests\Feature;

use App\Models\GtrTimingPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtrTimingPointAutoTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_is_auto_generated_per_type(): void
    {
        $start = GtrTimingPoint::create(['type' => 'start', 'name' => 'Start']);
        $finish = GtrTimingPoint::create(['type' => 'finish', 'name' => 'Finish']);
        $cp1 = GtrTimingPoint::create(['type' => 'checkpoint', 'name' => 'CP A']);
        $cp2 = GtrTimingPoint::create(['type' => 'checkpoint', 'name' => 'CP B']);
        $ws1 = GtrTimingPoint::create(['type' => 'water_station', 'name' => 'WS A']);

        $this->assertSame('START', $start->code);
        $this->assertSame('FINISH', $finish->code);
        $this->assertSame('CP1', $cp1->code);
        $this->assertSame('CP2', $cp2->code);
        $this->assertSame('WS1', $ws1->code);
    }

    public function test_duplicate_start_gets_numbered(): void
    {
        $a = GtrTimingPoint::create(['type' => 'start', 'name' => 'Start A']);
        $b = GtrTimingPoint::create(['type' => 'start', 'name' => 'Start B']);

        $this->assertSame('START', $a->code);
        $this->assertSame('START1', $b->code);
    }
}
