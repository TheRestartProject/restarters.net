<?php

namespace App\Listeners;

use App\Events\UserEmailUpdated;
use App\Events\UserLanguageUpdated;
use App\Events\UserRegistered;
use App\Services\DiscourseService;
use Illuminate\Support\Facades\Log;

class DiscourseUserEventSubscriber extends BaseEvent
{
    private $discourseClient;
    private $discourseService;

    public function __construct(DiscourseService $discourseService)
    {
        // NGM: preferable to switch to https://github.com/pnoeric/discourse-api-php ?
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        $this->discourseClient = app('discourse-client');
        $this->discourseService = $discourseService;
    }

    public function onUserEmailUpdated(UserEmailUpdated $event)
    {
        if (config('restarters.features.discourse_integration') === true)
        {
            $user = $event->user;

            // Only sync if the email actually changed.
            if (!$user->isDirty('email'))
            {
                return;
            }

            try
            {
                $this->discourseService->syncSso($user);
            } catch (\Exception $ex)
            {
                Log::error('Could not sync ' . $user->id . ' to Discourse: ' . $ex->getMessage());
            }
        }
    }

    public function onUserLanguageUpdated(UserLanguageUpdated $event)
    {
        if (config('restarters.features.discourse_integration') === true)
        {
            $user = $event->user;

            // Only sync if the language actually changed.
            if (!$user->isDirty('language'))
            {
                return;
            }

            try
            {
                $endpoint = "/users/by-external/{$user->id}.json";
                $response = $this->discourseClient->request(
                    'GET',
                    $endpoint
                );

                if ($response->getStatusCode() !== 200)
                {
                    Log::error(
                        'Could not sync ' . $user->id . ' language to Discourse: ' . $response->getReasonPhrase()
                    );
                }

                $json = json_decode($response->getBody()->getContents(), true);
                if (empty($json['user']))
                {
                    throw new \Exception("User {$user->id} not found in Discourse");
                }

                $userName = $json['user']['username'];

                // TODO: Discourse doesn't have e.g. fr-BE, so just going with main locale.
                $locale = explode('-', $user->language)[0];

                $endpoint = "/u/{$userName}.json";
                $response = $this->discourseClient->request(
                    'PUT',
                    $endpoint,
                    [
                        'form_params' => [
                            'locale' => $locale,
                        ]
                    ]
                );
            } catch (\Exception $ex)
            {
                Log::error('Could not sync ' . $user->id . ' language to Discourse: ' . $ex->getMessage());
            }
        }
    }

    public function onUserRegistered(UserRegistered $event)
    {
        if (config('restarters.features.discourse_integration') === true)
        {
            $user = $event->user;

            try
            {
                $this->discourseService->syncSso($user);
            } catch (\Exception $ex)
            {
                Log::error('Could not sync ' . $user->id . ' to Discourse: ' . $ex->getMessage());
            }
        }
    }

    public function onUserDeleted(UserDeleted $event)
    {
        if (config('restarters.features.discourse_integration') === true)
        {
            $user = $event->user;

            try
            {
                $this->discourseService->anonymise($user);
            } catch (\Exception $ex)
            {
                Log::error('Could not anonymise ' . $user->id . ' on Discourse: ' . $ex->getMessage());
            }
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        // We subscribe to all the events irrespective of whether the feature is enabled so that we can test them.
        $events->listen(
            \App\Events\UserEmailUpdated::class,
            'App\Listeners\DiscourseUserEventSubscriber@onUserEmailUpdated'
        );

        $events->listen(
            \App\Events\UserLanguageUpdated::class,
            'App\Listeners\DiscourseUserEventSubscriber@onUserLanguageUpdated'
        );

        $events->listen(
            \App\Events\UserRegistered::class,
            'App\Listeners\DiscourseUserEventSubscriber@onUserRegistered'
        );

        $events->listen(
            \App\Events\UserDeleted::class,
            'App\Listeners\DiscourseUserEventSubscriber@onUserDeleted'
        );
    }
}
