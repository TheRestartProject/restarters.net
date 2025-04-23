<?php

namespace Tests\Feature\Translations;

use App\Models\Group;
use App\Models\Party;
use App\Models\Role;
use App\Models\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class CheckTest extends TestCase
{
    public function testCheckTranslations(): void {
        chdir(base_path());
        $this->artisan('translations:check')->assertExitCode(0);
    }
}
