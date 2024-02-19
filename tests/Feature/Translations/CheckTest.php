<?php

namespace Tests\Feature\Translations;

use App\Group;
use App\Party;
use App\Role;
use App\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class CheckTest extends TestCase
{
    public function testCheckTranslations() {
        chdir(base_path());
        $this->artisan('translations:check')->assertExitCode(0);
    }
}
