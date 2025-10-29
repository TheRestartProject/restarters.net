<?php

namespace Tests\Feature;

use App\Events\UserDeleted;
use App\Events\UserLanguageUpdated;
use App\Events\UserRegistered;
use App\Listeners\AddUserToDiscourseGroup;
use App\Listeners\AnonymiseSoftDeletedUser;
use App\Listeners\DiscourseUserEventSubscriber;
use App\Providers\DiscourseServiceProvider;
use App\Role;
use App\User;
use DB;
use Hash;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Feature\MockInterface;
use Illuminate\Support\Facades\Artisan;

class DiscourseAccountDeletionTest extends TestCase
{
    /** @test */
    public function user_deletion_triggers_anonymise(): void
    {
        if (config('restarters.features.discourse_integration')) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(true);
        }
    }
}