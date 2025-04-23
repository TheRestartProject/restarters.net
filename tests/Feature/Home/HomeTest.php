<?php

namespace Tests\Feature\Dashboard;

use App\Providers\AppServiceProvider;
use App\Models\Role;
use DB;
use Hash;
use Tests\TestCase;

class HomeTest extends TestCase
{
    /**
     * @dataProvider landingPagesProvider
     */
    public function testLoggedOut($url): void
    {
        $response = $this->get($url);
        $response->assertSuccessful();
        $response->assertSee(__('landing.learn'));
        $response->assertSee('language-bar');
    }

    public function landingPagesProvider(): array {
        return [
            [ '/' ],
            [ '/about' ],
            [ '/user' ]
        ];
    }

    public function testLoggedIn(): void {
        $this->loginAsTestUser(Role::RESTARTER);
        $response = $this->get('/user');
        $response->assertRedirect(AppServiceProvider::HOME);
    }
}
