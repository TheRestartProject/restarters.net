<?php

namespace App\Services;

use App\Role;
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

    public function getAllUsers()
    {
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

            if (! $limited) {
                $users = $discourseResult->members;
                Log::info('...process '.count($users));

                if ($users && count($users)) {
                    foreach ($users as $user) {
                        $endpoint = "/admin/users/{$user->id}.json";
                        do {
                            Log::debug("Get user {$user->id}");
                            $response = $client->request('GET', $endpoint);
                            $discourseResult = json_decode($response->getBody());

                            if (! $discourseResult) {
                                // This also seems to happen as a transient error.
                                Log::debug("Get failed on {$user->id}");
                                sleep(1);
                                $limited = true;
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
                                    Log::debug('...got '.count($allUsers).' so far');
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

    public function syncGroups($idgroups = NULL) {
        $restartIds = $idgroups ? $idgroups : Group::whereNotNull('discourse_group')->pluck('idgroups');

        // Get all Discourse groups.  We need to find the ids matching the group name we store.  The groups.json
        // call doesn't return all the groups, so we query each one we know to find the id.
        Log::debug('Get list of Discourse groups');
        $client = app('discourse-client');

        foreach ($restartIds as $restartId) {
            $group = Group::find($restartId);
            $discourseName = $group->discourse_group;

            $response = $client->request('GET', "/g/$discourseName.json");

            if ($response->getStatusCode() == 200)
            {
                $discourseResult = json_decode($response->getBody(), true);

                $discourseId = $discourseResult['group']['id'];
                Log::debug("Sync members for Restarters group $restartId, {$group->discourse_group}, Discourse group $discourseId");

                if ($discourseResult['group']['messageable_level'] != 4) {
                    Log::debug("Update messageable_level for Restarters group $restartId, {$group->discourse_group}, Discourse group $discourseId");
                    $gData = $discourseResult['group'];
                    $gData['messageable_level'] = 4;
                    $response = $client->request('PUT', "/g/$discourseId.json", [
                        'form_params' => [
                            'group' => $gData
                        ]
                    ]);

                    if ($response->getStatusCode() === 200) {
                        Log::debug("...succeeded");
                    } else {
                        Log::debug("...failed with " . $response->getStatusCode() . ", " . $response->getBody());
                    }
                }

                if ($group->groupimage && $group->groupimage->idimages) {
                    // Check if the flair_url needs updating.  This keeps the logo in sync with changes on Restarters.
                    Log::debug("Check Discourse logo {$group->discourse_logo} vs {$group->groupimage->idimages}");
                    if (!$group->discourse_logo || $group->discourse_logo != $group->groupimage->image->idimages) {
                        // We need to update it.  To do that, we first have to upload the image file to Discourse.
                        Log::debug("Need to update flair_url with ". $group->groupImagePath());

                        // Upload an image.  We need the MIME type of the file.
                        $fh = fopen(public_path('uploads/mid_' . $group->groupImage->image->path),'r');

                        $data = [
                            [
                                'name' => 'upload_type',
                                'contents' => 'group_flair'
                            ],
                            [
                                'name' => 'type',
                                'contents' => mime_content_type($fh)
                            ],
                            [
                                'name' => 'sha1_checksum',
                                'contents' => sha1_file($group->groupImagePath())
                            ],
                            [
                                'name' => 'file',
                                'contents' => file_get_contents($group->groupImagePath()),
                                'filename' => 'GroupLogo' . $idgroups
                            ]
                        ];

                        $response = $client->request('POST', '/uploads.json', [
                            'multipart' => $data
                        ]);

                        Log::debug("Response from upload ". $response->getStatusCode() . " " . $response->getBody());

                        if ($response->getStatusCode() === 200) {
                            // Now we can update the group to use it.
                            $rsp = json_decode($response->getBody(), TRUE);

                            if ($rsp && array_key_exists('id', $rsp)) {
                                $uploadId = $rsp['id'];
                                $response = $client->request('PUT', "/g/$discourseId.json", [
                                    'form_params' => [
                                        'group' => [
                                            'flair_upload_id' => $uploadId
                                        ]
                                    ]
                                ]);

                                Log::debug("Response from flair_url update " . $response->getBody());

                                if ($response->getStatusCode() === 200) {
                                    // Update the group to record that we've uploaded, then we'll skip this next time
                                    // through.
                                    Log::debug("Updated flair url OK for {$discourseId} to {$group->groupimage->idimages}");
                                    $group->discourse_logo = $group->groupimage->image->idimages;
                                    $group->save();
                                    Log::debug("...saved update to group");
                                } else {
                                    Log::error("Failed to update flair url");
                                    throw new \Exception("Failed to update flair url for {$discourseId}");
                                }
                            } else {
                                Log::error("Failed to upload group logo for {$discourseId}");
                                throw new \Exception("Failed to upload group logo for {$discourseId}");
                            }
                        }
                    }
                }

                // TODO The Discourse API accepts up to around 1000 as the limit.  This is plenty for now, but
                // we will assert below if it turns out not to be in future.
                $limit = 1000;

                $response = $client->request('GET', "/groups/$discourseName/members.json?limit=$limit");

                Log::info('Response status: ' . $response->getStatusCode());

                if ($response->getStatusCode() != 200)
                {
                    Log::error("Failed to get list of members for {$discourseId}");
                    throw new \Exception("Failed to get list of members for {$discourseId}");
                } else {
                    $discourseResult = json_decode($response->getBody(), true);
                    $total = $discourseResult['meta']['total'];
                    Log::debug("Total $total");

                    if ($total > $limit)
                    {
                        Log::error("Group $discourseId too large at $total");
                        throw new \Exception("Group $discourseId too large at $total");
                    }

                    // Save off the members and whether they're an admin.
                    $discourseMembers = [];

                    foreach ($discourseResult['members'] as $d) {
                        $discourseMembers[$d['username']] = $d;
                        $discourseMembers[$d['username']]['owner'] = false;
                    }

                    foreach ($discourseResult['owners'] as $d) {
                        $discourseMembers[$d['username']] = $d;
                        $discourseMembers[$d['username']]['owner'] = true;
                    }

                    $restartersMembersUGs = UserGroups::where('group', $restartId)->where('status', '=', 1)->get();

                    $restartersMembers = [];

                    foreach ($restartersMembersUGs as $r) {
                        $u = User::find($r->user);
                        $restartersMembers[$u->username] = $r;
                    }
                    $discourseMembers = array_column($discourseResult['members'], 'username');
                    $restartersMembersIds = UserGroups::where('group', $restartId)->where('status', '=', 1)->whereNull('deleted_at')->pluck(
                        'user'
                    )->toArray();
                    $restartersMembers = User::whereIn('id', $restartersMembersIds)->pluck('username')->toArray();

                    Log::debug(
                        count($discourseMembers) . " Discourse members vs " . count(
                            $restartersMembers
                        ) . " on Restarters"
                    );
                    Log::debug("Discourse Members " . json_encode($discourseMembers));
                    Log::debug("Restarter Members " . json_encode($restartersMembers));

                    foreach ($discourseMembers as $discourseMember => $d) {
                        if (!array_key_exists($discourseMember, $restartersMembers)) {
                            Log::debug("Remove user $discourseMember from Discourse group $discourseName");

                            $response = $client->request('DELETE', "/admin/groups/$discourseId/members.json", [
                                'form_params' => [
                                    'usernames' => [ $discourseMember ]
                                ]
                            ]);

                            Log::info('Response status: ' . $response->getStatusCode());
                            Log::debug($response->getBody());

                            if ($response->getStatusCode() != 200)
                            {
                                Log::error("Failed to add member $discourseMember for {$discourseId} {$discourseName}");
                                throw new \Exception("Failed to add member $discourseMember for {$discourseId} {$discourseName}");
                            }
                        } else {
                            // See whether the owner status on Discourse matches the status on Restarters.
                            $role = $restartersMembers[$discourseMember]->role;
                            $shouldBeOwner = $role == Role::HOST;
                            Log::debug("Role for $discourseMember is $role, should be admin? $shouldBeOwner");

                            if ($d['owner'] && !$shouldBeOwner) {
                                Log::info("Remove $discourseMember as admin of {$discourseId} {$discourseName}");
                                $response = $client->request('DELETE', "/admin/groups/$discourseId/owners.json", [
                                    'user_id' => $d['id']
                                ]);

                                Log::info('Response status: ' . $response->getStatusCode());
                                Log::debug($response->getBody());

                                if ($response->getStatusCode() != 200)
                                {
                                    Log::error("Failed to remove $discourseMember as owner of {$discourseId} {$discourseName}");
                                    throw new \Exception("Failed to remove $discourseMember as owner of {$discourseId} {$discourseName}");
                                }
                            } else if (!$d['owner'] && $shouldBeOwner) {
                                Log::info("Add $discourseMember as admin of {$discourseId} {$discourseName}");
                                $response = $client->request('PUT', "/admin/groups/$discourseId/owners.json", [
                                    'form_params' => [
                                        'group' => [
                                            'usernames' => $discourseMember
                                        ]
                                    ]
                                ]);

                                Log::info('Response status: ' . $response->getStatusCode());
                                Log::debug($response->getBody());

                                if ($response->getStatusCode() != 200)
                                {
                                    Log::error("Failed to add $discourseMember as owner of {$discourseId} {$discourseName}");
                                    throw new \Exception("Failed to add $discourseMember as owner of {$discourseId} {$discourseName}");
                                }
                            }
                        }
                    }

                    foreach ($restartersMembers as $restartersMember => $r) {
                        if (!array_key_exists($restartersMember, $discourseMembers)) {
                            Log::debug("Add Restarter user $restartersMember to Discourse group $discourseName");

                            // We add these one by one, rather than in a single call.  This is because if our Restarters
                            // usernames don't match the Discourse ones, e.g. due to anonymisation, then the single
                            // call would fail.
                            $response = $client->request('PUT', "/admin/groups/$discourseId/members.json", [
                                'form_params' => [
                                    'usernames' => $restartersMember
                                ]
                            ]);

                            Log::debug('Response status: ' . $response->getStatusCode());
                            Log::debug($response->getBody());

                            if ($response->getStatusCode() != 200)
                            {
                                Log::error("Failed to add member for {$discourseId} {$discourseName}");
                            } else
                            {
                                Log::info("Added Restarter user $restartersMember to Discourse group $discourseName");
                            }
                        }
                    }
                }
            }
        }
    }
}
