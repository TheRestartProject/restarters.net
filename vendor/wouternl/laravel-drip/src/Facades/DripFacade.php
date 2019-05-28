<?php
namespace wouterNL\Drip\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

class DripFacade extends IlluminateFacade
{
    protected static function getFacadeAccessor()
    {
        return 'Drip';
    }
}
