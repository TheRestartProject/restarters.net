<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        'UseCDNCache',
        'UseDC',
        'wiki_db_mw__session',
        'wiki_db_mw_Token',
        'wiki_db_mw_UserID',
        'wiki_db_mw_UserName',
        'wiki_test_session',
        'wiki_testToken',
        'wiki_testUserID',
        'wiki_testUserName',
        'wiki_devToken',
        'wiki_devUserID',
        'wiki_devUserName',
        'wiki_dev_mw__session',
        'wiki_dev_mw_Token',
        'wiki_dev_mw_UserID',
        'wiki_dev_mw_UserName',
        'authenticated',
        'restarters_apitoken'
    ];
}
