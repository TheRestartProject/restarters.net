<?php

namespace App;

use App\User;
use Drip;
use Illuminate\Database\Eloquent\Model;

class DripEvent extends Model
{
    /*
    |--------------------------------------------------------------------------
    | DripEvent
    |--------------------------------------------------------------------------
    |
    | Events:
    | -- createOrUpdateSubscriber(User $user)
    */

    /**
     * [createOrUpdateSubscriber description]
     * Create or Update a Subscriber
     * @author Christopher Kelker
     * @date   2019-05-29T15:55:02+010
     * @return [type]
     */
    public static function createOrUpdateSubscriber(User $user, $opted_in = false, $old_email = false, $new_email = false)
    {
        if ($opted_in) {
            $tags = (array) [
        'subscriber unconfirmed',
      ];

            $remove_tags = (array) [
        'unsubscribed',
      ];

            $custom_fields = (array) [
          'list_newsletter_general' => 'Subscribe',
        'gdpr_consent_granted_date' => date('Y-m-d'),
        'gdpr_consent_granted_context' => 'restarters__registration',
        'signup_source' => url('/user/register'),
      ];
        }

        if (! $opted_in) {
            $tags = (array) [
        'subscriber unconfirmed',
      ];

            $remove_tags = (array) [
      ];

            $custom_fields = (array) [
        'signup_source' => url('/user/register'),
      ];
        }

        $parameters = [
      'subscriber_id' => $user->drip_subscriber_id,
      'email' => $user->email,
      'account_id' => env('DRIP_ACCOUNT_ID'),
      'status' => 'active',
      'time_zone' => '',
      'tags' => (array) $tags,
      'remove_tags' => (array) $remove_tags,
      'custom_fields' => (object) $custom_fields,
    ];

        // Remove new email because it would've been set already
        if (! empty($old_email) && ! empty($new_email) && $old_email != $new_email) {
            unset($parameters['email']);
            $parameters['email'] = $old_email;
            $parameters['new_email'] = $new_email;
        }

        $subscriber = Drip::createOrUpdateSubscriber($parameters);

        return (object) $subscriber;
    }
}
