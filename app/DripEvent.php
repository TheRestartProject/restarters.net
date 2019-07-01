<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
use Drip;

class DripEvent extends Model
{
  /*
  |--------------------------------------------------------------------------
  | DripEvent
  |--------------------------------------------------------------------------
  |
  | Events:
  | -- findSubscriber(User $user)
  | -- createOrUpdateSubscriber(User $user)
  | -- subscribeSubscriberToNewsletter(User $user)
  | -- unsubscribeSubscriberFromNewsletter(User $user)
  | -- deleteSubscriber(User $user)
  */

  /**
   * [findSubscriber description]
   * Find an existing Subscriber
   * @author Christopher Kelker
   * @date   2019-05-29T15:48:07+010
   * @param  User                    $user
   * @return [type]
   */
  public static function findSubscriber(User $user)
  {
    $parameters = [
      "subscriber_id" => $user->drip_subscriber_id,
      "account_id" => env('DRIP_ACCOUNT_ID'),
      "email" => $user->email,
    ];

    $subscriber = Drip::fetchSubscriber($parameters);

    return (object) $subscriber;
  }

  /**
   * [createOrUpdateSubscriber description]
   * Create or Update a Subscriber
   * @author Christopher Kelker
   * @date   2019-05-29T15:55:02+010
   * @param  User                    $user
   * @return [type]
   */
  public static function createOrUpdateSubscriber(User $user, $opted_in = false, $old_email = false, $new_email = false)
  {
    if ($opted_in) {
      $tags = (array) [
        "subscriber confirmed",
      ];

      $remove_tags = (array) [
        "unsubscribed",
      ];

      $custom_fields = (array) [
        "gdpr_consent_granted_date" => date('Y-m-d'),
        "gdpr_consent_granted_context" => "restarters__registration",
        "signup_source" => url("/user/register"),
      ];
    }

    if ( ! $opted_in) {
      $tags = (array) [
        "subscriber unconfirmed",
      ];

      $remove_tags = (array) [
      ];

      $custom_fields = (array) [
        "signup_source" => url("/user/register"),
      ];
    }

    $parameters = [
      "subscriber_id" => $user->drip_subscriber_id,
      "email" => $user->email,
      "account_id" => env('DRIP_ACCOUNT_ID'),
      "status" => "active",
      "time_zone" => "",
      "tags" => (array) $tags,
      "remove_tags" => (array) $remove_tags,
      "custom_fields" => (object) $custom_fields,
    ];

    // Remove new email because it would've been set already
    if ( ! empty($old_email) && ! empty($new_email) && $old_email != $new_email ) {
      unset($parameters['email']);
      $parameters['email'] = $old_email;
      $parameters['new_email'] = $new_email;
    }

    $subscriber = Drip::createOrUpdateSubscriber($parameters);

    return (object) $subscriber;
  }


  /**
   * [subscribeSubscriberToNewsletter description]
   * Subscribe an existing Subscriber or create a new one and Subscribe
   * them to the Newsletter: i.e.
   * https://www.getdrip.com/{DRIP_ACCOUNT_ID}/campaigns/{DRIP_CAMPAIGN_ID}
   * @author Christopher Kelker
   * @date   2019-05-29T15:45:50+010
   * @param  User                    $user
   * @return [type]
   */
  public static function subscribeSubscriberToNewsletter(User $user)
  {
    $parameters = [
      "subscriber_id" => $user->drip_subscriber_id,
      "campaign_id" => env('DRIP_CAMPAIGN_ID'),
      "account_id" => env('DRIP_ACCOUNT_ID'),
      "status" => "active",
      "email" => $user->email,
      "time_zone" => "",
      "custom_fields" => (object) [
        "name" => $user->name,
      ],
      "tags" => [
        "Customer", $user->email, url('/'),
      ],
    ];

    // Returns Subscriber
    $requested_subscriber = Drip::subscribeSubscriber($parameters);

    return (object) $requested_subscriber;
  }

  /**
   * [unsubscribeSubscriberFromNewsletter description]
   * Unsubscribe an existing Subscriber from a Newsletter
   * @author Christopher Kelker
   * @date   2019-05-29T15:50:44+010
   * @param  User                    $user
   * @return [type]
   */
  public static function unsubscribeSubscriberFromNewsletter(User $user)
  {
    $parameters = [
      "subscriber_id" => $user->drip_subscriber_id,
      "campaign_id" => env('DRIP_CAMPAIGN_ID'),
      "account_id" => env('DRIP_ACCOUNT_ID'),
      "status" => "active",
      "email" => $user->email,
      "time_zone" => "",
      "custom_fields" => (object) [
        "name" => $user->name,
      ],
      "tags" => [
        "Customer", $user->email, url('/'),
      ],
    ];

    // Returns Subscriber
    $unsubscribed = Drip::unsubscribeSubscriber($parameters);

    return is_array($unsubscribed) ? true : false;
  }

  /**
   * [deleteSubscriber description]
   * Delete a Subscriber entirely
   * @author Christopher Kelker
   * @date   2019-05-29T16:22:31+010
   * @param  User                    $user
   * @return [type]
   */
  public static function deleteSubscriber(User $user)
  {
    $url = 'https://api.getdrip.com/v2/'.env('DRIP_ACCOUNT_ID').'/subscribers/'.$user->drip_subscriber_id;

    // First parameter is the Subscriber path, the second is the parameters,
    // in which non are required for this action and the last is to DELETE the
    // Subscriber
    $request = Drip::makeRequest($url, [], 3);

    // If the request status is true, then the Subscriber has already been
    // deleted or does not exist.
    // if ( $request['status']) {
    //   return false;
    // }

    return true;
  }

}
