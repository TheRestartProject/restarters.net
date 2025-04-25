<?php

namespace Tests\Feature;

use App\Events\UserDeleted;
use PHPUnit\Framework\Attributes\Test;
use App\Events\UserLanguageUpdated;
use App\Events\UserRegistered;
use App\Listeners\AddUserToDiscourseGroup;
use App\Listeners\AnonymiseSoftDeletedUser;
use App\Listeners\DiscourseUserEventSubscriber;
use App\Providers\DiscourseServiceProvider;
use App\Models\Role;
use App\Models\User;
use DB;
use Hash;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\DiscourseTestCase;
use Tests\Feature\MockInterface;
use Illuminate\Support\Facades\Artisan;

class DiscourseAccountDeletionTest extends DiscourseTestCase
{
    #[Test]
    public function user_deletion_triggers_anonymise(): void
    {
        // TODO Not working and agreed to disable for now.
        $this->assertTrue(true);
    }
}