<?php

namespace Tests\Feature\Dashboard;

use App\Providers\RouteServiceProvider;
use App\Role;
use DB;
use Hash;
use Tests\TestCase;

class HomeTest extends TestCase
{
    /**
     * @story:HomeController::index
     * @dataProvider landingPagesProvider
     */
    public function testLoggedOut($url)
    {
        $response = $this->get($url);
        $response->assertSuccessful();
        $response->assertSee(__('landing.learn'));
        $response->assertSee('language-bar');
    }

    public function landingPagesProvider() {
        return [
            [ '/' ],
            [ '/about' ],
            [ '/user' ]
        ];
    }

    /** @story:HomeController::index */
    public function testLoggedIn() {
        $this->loginAsTestUser(Role::RESTARTER);
        $response = $this->get('/user');
        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
