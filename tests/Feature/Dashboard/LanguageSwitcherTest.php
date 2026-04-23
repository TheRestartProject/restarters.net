<?php

namespace Tests\Feature\Dashboard;

use App\Role;
use App\Session;
use DB;
use Hash;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class LanguageSwitcherTest extends TestCase
{
    public function testSwitchEndpoint(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $user = Auth::user();

        $rsp = $this->get('/set-lang/de');
        $this->assertTrue($rsp->isRedirection());
        $user->refresh();
        assertEquals('de', $user->language);
        $rsp = $this->get('/set-lang/en');
        $this->assertTrue($rsp->isRedirection());
        $user->refresh();
        assertEquals('en', $user->language);
    }

    public function testMiddleware(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $user = Auth::user();

        $rsp = $this->get('/?locale=de');
        $user->refresh();
        assertEquals('de', $user->language);
        $rsp = $this->get('/?locale=en');
        $user->refresh();
        assertEquals('en', $user->language);
    }

    public function testMiddlewareHeader(): void
    {
        // Passing get headers doesn't seem to be working, but this'll do.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';
        $this->withSession([
                               'locale' => 'UT'
                           ])->get('/fixometer')->assertSee(' Deutsch</button>', false);
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        $this->withSession([
                               'locale' => 'UT'
                           ])->get('/fixometer')->assertSee(' English</button>', false);
    }
}
