<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Website GTR: Race Info → Race Village (peta 3D venue). */
class GtrRaceVillageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_embeds_the_3d_map(): void
    {
        $this->get(route('gtr.village'))
            ->assertOk()
            ->assertSee('Race Village')
            ->assertSee('<iframe src="/assets/gtr/race-village/Peta-Race-Village-GTR-web/index.html?v=', false)
            ->assertSee('Denah 2D')
            ->assertSee('data-rv-mode="/assets/gtr/race-village/Peta-Race-Village-GTR-web/denah-2d.html?v=', false);
    }

    public function test_race_info_menu_links_to_race_village(): void
    {
        $this->get(route('gtr.rules'))
            ->assertOk()
            ->assertSee('Race Village · Peta 3D')
            ->assertSee(route('gtr.village'));
    }
}
