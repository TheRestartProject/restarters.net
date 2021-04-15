<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Listeners\DiscourseUserEventSubscriber;
use App\User;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class DiscourseAccountCreationTests extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    /** @test */
    public function user_registration_triggers_user_registered_event()
    {
        $this->withoutExceptionHandling();
        Event::fake();

        // arrange
        app('honeypot')->disable();

        $userAttributes = [];
        $userAttributes['name'] = 'Neil';
        $userAttributes['email'] = 'neil@doubleloop.net';
        $userAttributes['age'] = '1982';
        $userAttributes['country'] = 'GBR';
        $userAttributes['password'] = 'letmein';
        $userAttributes['password_confirmation'] = 'letmein';
        $userAttributes['my_time'] = Carbon::now();
        $userAttributes['consent_gdpr'] = true;
        $userAttributes['consent_future_data'] = true;

        // act
        $response = $this->post('/user/register/', $userAttributes);

        // assert
        Event::assertDispatched(UserRegistered::class);
    }

    /** @test */
    public function user_registration_triggers_discourse_sync_attempt()
    {
        $this->withoutExceptionHandling();
        // arrange
        $this->instance(DiscourseUserEventSubscriber::class, Mockery::mock(DiscourseUserEventSubscriber::class, function ($mock) {
            $mock->shouldReceive('onUserRegistered')->once();
        }));

        config(['restarters.features.discourse_integration' => true]);
        app('honeypot')->disable();

        $userAttributes = [];
        $userAttributes['name'] = 'Neil';
        $userAttributes['email'] = 'neil@doubleloop.net';
        $userAttributes['age'] = '1982';
        $userAttributes['country'] = 'GBR';
        $userAttributes['password'] = 'letmein';
        $userAttributes['password_confirmation'] = 'letmein';
        $userAttributes['my_time'] = Carbon::now();
        $userAttributes['consent_gdpr'] = true;
        $userAttributes['consent_future_data'] = true;

        // act
        $response = $this->post('/user/register/', $userAttributes);
    }

    /** @test */
    public function user_registration_discourse_sync_attempt()
    {
        $this->withoutExceptionHandling();
        // arrange
        config(['restarters.features.discourse_integration' => true]);
        config(['discourse-api.base_url' => 'https://talk.restarters.dev']);
        config(['discourse-api.sso_secret' => 'XXX']); // change XXX to sso secret when testing against test Discourse instance.
        config(['discourse-api.api_key' => 'XXX']); // change XXX when testing against test Discourse instance.
        config(['discourse-api.api_username' => 'neil']);
        app('honeypot')->disable();

        $userAttributes = [];
        $userAttributes['name'] = bin2hex(random_bytes(2));
        $userAttributes['email'] = $userAttributes['name'].'@restarters.dev';
        $userAttributes['age'] = '1982';
        $userAttributes['country'] = 'GBR';
        $userAttributes['password'] = 'letmein';
        $userAttributes['password_confirmation'] = 'letmein';
        $userAttributes['my_time'] = Carbon::now();
        $userAttributes['consent_gdpr'] = true;
        $userAttributes['consent_future_data'] = true;

        // act
        $response = $this->post('/user/register/', $userAttributes);
    }
}
