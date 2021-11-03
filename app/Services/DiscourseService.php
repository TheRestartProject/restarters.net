<?php

namespace App\Services;

use App\User;
use App\Group;
use App\UserGroups;
use Auth;
use Illuminate\Support\Facades\Log;

class DiscourseService
{
    public function getDiscussionTopics($tag = null, $numberOfTopics = 5)
    {
        if (! config('restarters.features.discourse_integration')) {
            return [];
        }

        $topics = [];

        try {
            $client = app('discourse-client');

            $endpoint = $tag ? "/tag/{$tag}/l/latest.json" : '/latest.json';
            $response = $client->request('GET', $endpoint);
            $discourseResult = json_decode($response->getBody());

            $topics = $discourseResult->topic_list->topics;

            if ($topics) {
                if (! empty($numberOfTopics)) {
                    $topics = array_slice($topics, 0, $numberOfTopics, true);
                }

                $endpoint = '/site.json';
                $response = $client->request('GET', $endpoint);
                $discourseResult = json_decode($response->getBody());
                $categories = $discourseResult->categories;

                foreach ($topics as $topic) {
                    foreach ($categories as $category) {
                        if ($topic->category_id == $category->id) {
                            $topic->category = $category;
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            Log::error('Error retrieving discussion topics'.$ex->getMessage());
        }

        return $topics;
    }

    public function getUserIdsByBadge($badgeId)
    {
        if (! config('restarters.features.discourse_integration')) {
            return [];
        }

        $externalUserIds = [];

        try {
            $client = app('discourse-client');

            $endpoint = "/user_badges.json?badge_id={$badgeId}";
            $response = $client->request('GET', $endpoint);
            if ($response->getStatusCode() == 404) {
                Log::error("{$endpoint} not found");
                throw new \Exception("{$endpoint} not found");
            }
            $discourseResult = json_decode($response->getBody());

            $users = $discourseResult->users;

            foreach ($users as $user) {
                $endpoint = "/admin/users/{$user->id}.json";
                $response = $client->request('GET', $endpoint);
                $discourseResult = json_decode($response->getBody());
                $externalUserIds[] = [
                    'external_id' => $discourseResult->single_sign_on_record->external_id,
                    'username' => $discourseResult->single_sign_on_record->external_username,
                ];
                $this->avoidRateLimiting();
            }
        } catch (\Exception $ex) {
            Log::error('Error retrieving users by badge: '.$ex->getMessage());
        }

        return $externalUserIds;
    }

    protected function avoidRateLimiting()
    {
        // Sleep to avoid Discourse rate limiting of 60 requests per minute.
        // See https://meta.discourse.org/t/global-rate-limits-and-throttling-in-discourse/78612
        // There's probably a better way of doing this.
        sleep(1);
    }

    public function addUserToPrivateMessage($threadid, $addBy, $addUser)
    {
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        Log::info("Add user to private message $threadid, $addBy, $addUser");

        $client = app('discourse-client', [
            'username' => $addBy,
        ]);

        $params = [
            'user' => $addUser,
            'custom_message' => __('events.discourse_invite'),
        ];

        $endpoint = "/t/$threadid/invite";

        Log::info('Adding to private message: '.json_encode($params));
        $response = $client->request(
            'POST',
            $endpoint,
            [
                'form_params' => $params,
            ]
        );

        Log::info('Response status: '.$response->getStatusCode());
        Log::info('Response body: '.$response->getBody());

        if (! $response->getStatusCode() === 200) {
            Log::error("Could not add to private message ($threadid, $addBy, $addUser:".$response->getReasonPhrase());
        }
    }

    public function getAllUsers() {
        if (! config('restarters.features.discourse_integration')) {
            return [];
        }

        // As per https://meta.discourse.org/t/how-do-i-get-a-list-of-all-users-from-the-api/24261/9 we can
        // clunkily get a list of all users by looking for the trust_level_0 group, then fetch each
        // one to get the email.
        //
        // This
        $allUsers = [];

        // Don't catch any exceptions, because we want the failure to ripple up rather than return a truncated list
        // with apparent success.
        $client = app('discourse-client');
        $offset = 0;

        do {
            $endpoint = "groups/trust_level_0/members.json?limit=50&offset=$offset";
            $response = $client->request('GET', $endpoint);
            if ($response->getStatusCode() == 404) {
                Log::error("{$endpoint} not found");
                throw new \Exception("{$endpoint} not found");
            }
            $discourseResult = json_decode($response->getBody());

            // We seem to get rate-limited in a way that the 429 retrying doesn't cover, so spot that here.
            $limited = property_exists($discourseResult, 'error_type') && $discourseResult->error_type == 'rate_limit';

            if (!$limited) {
                $users = $discourseResult->members;
                Log::info('...process ' . count($users));

                if ($users && count($users)) {
                    foreach ($users as $user) {
                        $endpoint = "/admin/users/{$user->id}.json";
                        do {
                            Log::debug("Get user {$user->id}");
                            $response = $client->request('GET', $endpoint);
                            $discourseResult = json_decode($response->getBody());

                            if (!$discourseResult) {
                                # This also seems to happen as a transient error.
                                Log::debug("Get failed on {$user->id}");
                                sleep(1);
                                $limited = TRUE;
//                                throw new \Exception("Get of $endpoint failed");
                            } else {
                                $limited = property_exists(
                                        $discourseResult,
                                        'error_type'
                                    ) && $discourseResult->error_type == 'rate_limit';

                                if ($limited) {
                                    Log::debug("Limited on {$user->id}");
                                    sleep(1);
                                } else {
                                    $allUsers[] = $discourseResult;
                                    Log::debug('...got ' . count($allUsers) . " so far");
                                }
                            }
                        } while ($limited);
                    }
                }

                $offset += 50;
            } else {
                Log::debug('...rate limited, sleep');
            }
        } while ($limited || count($users));

        return $allUsers;
    }

    public function syncUsersToGroups($idgroups = NULL) {
        $ids = $idgroups ? $idgroups : Group::whereNotNull('discourse_group')->pluck('idgroups');

        // Get all Discourse groups.  We need to find the ids matching the group name we store.
        Log::debug('Get list of Discourse groups');
        $client = app('discourse-client');
        $response = $client->request('GET', '/groups.json');

        Log::info('Response status: '.$response->getStatusCode());

        $discourseGroupForRestartersGroup = [];

        if ($response->getStatusCode() != 200 ) {
            Log::error('Failed to get list of groups from Discourse');
            throw new \Exception('Failed to get list of groups from Discourse');
        }

        $discourseResult = json_decode($response->getBody(), TRUE);

        foreach ($discourseResult['groups'] as $discourseGroup) {
            foreach ($ids as $id) {
                $group = Group::find($id);

                if ($group && $group->discourse_group && $group->discourse_group == $discourseGroup['name']) {
                    Log::debug("Restarters group $id, {$group->discourse_group} is Discourse group {$discourseGroup['id']}");
                    $discourseGroupForRestartersGroup[$id] = [
                        'discourseId' => $discourseGroup['id'],
                        'discourseName' => $group->discourse_group
                    ];
                }
            }
        }

        foreach ($discourseGroupForRestartersGroup as $restartId => $info)
        {
            $discourseId = $info['discourseId'];
            $discourseName = $info['discourseName'];
            $group = Group::find($restartId);

            Log::debug("Sync members for $restartId => $discourseId $discourseName");

            // TODO The Discourse API accepts up to around 1000 as the limit.  This is plenty for now, but
            // we will assert below if it turns out not to be in future.
            $limit = 1000;

            $response = $client->request('GET', "/groups/$discourseName/members.json?limit=$limit");

            Log::info('Response status: ' . $response->getStatusCode());

            if ($response->getStatusCode() != 200)
            {
                Log::error("Failed to get list of members for {$discourseId}");
                throw new \Exception("Failed to get list of members for {$discourseId}");
            } else
            {
                Log::debug($response->getBody());
                $discourseResult = json_decode($response->getBody(), true);
                $total = $discourseResult['meta']['total'];
                Log::debug("Total $total");

                if ($total > $limit) {
                    Log::error("Group $discourseId too large at $total");
                    throw new \Exception("Group $discourseId too large at $total");
                }

                // TODO Check members against Restarters members.
                $discourseMembers = array_column($discourseResult['members'], 'id');
                $restartersMembers = UserGroups::where('group', $restartId)->where('status', '=', 1)->pluck('user');

                Log::debug(count($discourseMembers) . " Discourse members vs " . count($restartersMembers) . " on Restarters");
                Log::debug("Discourse Members " . json_encode($discourseMembers));
                Log::debug("Restarter Members " . json_encode($restartersMembers));

                foreach ($discourseMembers as $discourseMember) {
                    if (!in_array($discourseMember, $restartersMembers)) {
                        Log::info("Remove Discourse member $discourseMember from $discourseName as no longer a member on Restarters");
                        // TODO Implement.
                    }
                }

                $toadd = [];

                foreach ($restartersMembers as $restartersMember) {
                    if (!in_array($restartersMember, $discourseMembers)) {
                        Log::info("Add Restarter user #$restartersMember to Discourse group $discourseName");

                        // Find the username (i.e. how they are known on Discourse).
                        $u = User::find($restartersMember);
                        $toadd[] = $u->username;
                    }
                }

                if (count($toadd)) {
                    Log::debug("Add " . json_encode(implode(',', $toadd)));

                    $response = $client->request('PUT', "/admin/groups/$discourseId/members.json", [
                        'form_params' => [
                            'usernames' => implode(',', $toadd)
                        ]
                    ]);

                    Log::info('Response status: ' . $response->getStatusCode());
                    Log::debug($response->getBody());

                    if ($response->getStatusCode() != 200)
                    {
                        Log::error("Failed to add members for {$discourseId} {$discourseName}");
                        throw new \Exception("Failed to add members for {$discourseId} {$discourseName}");
                    } else {
                        $discourseResult = json_decode($response->getBody(), true);
                    }
                }
            }
        }
    }
}
