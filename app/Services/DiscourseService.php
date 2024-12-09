<?php

namespace App\Services;

use App\Network;
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

    public function removeUserFromPrivateMessage($threadid, $removeBy, $removeUser) {
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        Log::info("Remove user from private message $threadid, $removeBy, $removeUser");

        $client = app('discourse-client', [
            'username' => $removeBy,
        ]);

        $params = [
            'username' => $removeUser,
        ];

        $endpoint = "/t/$threadid/remove-allowed-user";

        Log::info('Removing from private message: '.json_encode($params));
        $response = $client->request(
            'PUT',
            $endpoint,
            [
                'form_params' => $params,
            ]
        );

        Log::info('Response status: '.$response->getStatusCode());
        Log::info('Response body: '.$response->getBody());

        if (! $response->getStatusCode() === 200) {
            Log::error("Could not remove from private message ($threadid, $removeBy, $removeUser:".$response->getReasonPhrase());
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
            // Sleep so that we don't hit the Discourse or Communiteq rate limits.
            usleep(500000);

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

                    $restartersMembersUGs = UserGroups::where('group', $restartId)->where('status', '=', 1)->whereNull('deleted_at')->get();

                    $restartersMembers = [];

                    foreach ($restartersMembersUGs as $r) {
                        $u = User::find($r->user);

                        if (!strlen($u->username)) {
                            // No current user.  Create one.
                            $u->generateAndSetUsername();
                            $u->save();
                            $u->refresh();
                            $this->syncSso($u);
                        }

                        $restartersMembers[$u->username] = $r;
                    }

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
                                    'user_id' => $d['id']
                                ]
                            ]);

                            Log::info('Response status: ' . $response->getStatusCode());
                            Log::debug($response->getBody());

                            if ($response->getStatusCode() != 200)
                            {
                                Log::error("Failed to remove member $discourseMember for {$discourseId} {$discourseName}");
                                throw new \Exception("Failed to remove member $discourseMember for {$discourseId} {$discourseName}");
                            }
                        } else {
                            // See whether the owner status on Discourse matches the status on Restarters.
                            $role = $restartersMembers[$discourseMember]->role;
                            $shouldBeOwner = $role == Role::HOST;
                            Log::debug("Role for $discourseMember is $role, should be admin? $shouldBeOwner");

                            if ($d['owner'] && !$shouldBeOwner) {
                                Log::info("Remove $discourseMember as admin of {$discourseId} {$discourseName}");
                                $response = $client->request('DELETE', "/admin/groups/$discourseId/owners.json", [
                                    'form_params' => [
                                        'user_id' => $d['id']
                                    ]
                                ]);

                                Log::info('Response status: ' . $response->getStatusCode());
                                Log::debug($response->getBody());

                                if ($response->getStatusCode() != 200)
                                {
                                    Log::error("Failed to remove $discourseMember as owner of {$discourseId} {$discourseName}");
                                    #throw new \Exception("Failed to remove $discourseMember as owner of {$discourseId} {$discourseName}");
                                }
                            } else if (!$d['owner'] && $shouldBeOwner) {
                                Log::info("Add $discourseMember as admin of {$discourseId} {$discourseName}");
                                $response = $client->request('PUT', "/groups/$discourseId/owners.json", [
                                    'form_params' => [
                                        'usernames' => $discourseMember
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

                            if ($restartersMembers[$discourseMember]->role == Role::HOST && $d['trust_level'] == 0) {
                                Log::info("$discourseMember is on trust_level 0, promote");
                                $response = $client->request('PUT', "/admin/users/{$d['id']}/trust_level.json", [
                                    'form_params' => [
                                        'level' => 1
                                    ]
                                ]);

                                Log::info('Response status: ' . $response->getStatusCode());
                                Log::debug($response->getBody());

                                if ($response->getStatusCode() != 200) {
                                    Log::error("Failed to promote $discourseMember to trust level 1");
                                    throw new \Exception("Failed to promote $discourseMember to trust level 1");
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
                            $response = $client->request('PUT', "/groups/$discourseId/members.json", [
                                'form_params' => [
                                    'usernames' => $restartersMember
                                ]
                            ]);

                            Log::debug('Response status: ' . $response->getStatusCode());
                            Log::debug($response->getBody());

                            if ($response->getStatusCode() == 400) {
                                // This happens if the user doesn't exist on Discourse.  That can happen for historical
                                // reasons, or failures during user creation.  Try to create them.
                                Log::info('Create missing member on Discourse ' . $restartersMember);
                                $u = User::where('username', $restartersMember)->first();
                                $this->syncSso($u);

                                // Now try again to add.  If this fails we'll pick it up again next time through.
                                $response = $client->request('PUT', "/groups/$discourseId/members.json", [
                                    'form_params' => [
                                        'usernames' => $restartersMember
                                    ]
                                ]);
                            } else if ($response->getStatusCode() != 200)
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

    public function syncSso($user)
    {
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        $endpoint = '/admin/users/sync_sso';

        // see https://meta.discourse.org/t/sync-sso-user-data-with-the-sync-sso-route/84398 for details on the sync_sso route.
        $sso_secret = config('discourse-api.sso_secret');

        // We have to send all these details, even if they are not
        // being updated, as otherwise they are blanked in the Discourse
        // SSO values.  Discourse is currently configured to take only email from SSO values, but better not to blank them regardless.
        $sso_params = [
            'external_id' => $user->id,
            'email' => $user->email,
            'username' => $user->username,
            'name' => $user->name,
        ];
        $sso_payload = base64_encode(http_build_query($sso_params));
        $sig = hash_hmac('sha256', $sso_payload, $sso_secret);

        $client = app('discourse-client');

        $response = $client->request(
            'POST',
            $endpoint,
            [
                'form_params' => [
                    'sso' => $sso_payload,
                    'sig' => $sig,
                ],
            ]
        );

        if ($response->getStatusCode() !== 200) {
            Log::error('Could not sync user '.$user->id.' to Discourse: '.$response->getReasonPhrase());
        } else {
            Log::debug('Sync '.$user->id.' to Discourse OK');
        }

        if ($user->repair_network) {
            $network = Network::find($user->repair_network);

            if ($network->name === 'Repair café Haut-de-France') {
                // Special case - ensure that emails are turned off.  This is primarily turning off email_digests.
                // Nothing else should ever be happening on Discourse for these users.
                $worked = false;

                $response = $client->request(
                    'GET',
                    "/users/by-external/{$user->id}.json"
                );

                if ($response->getStatusCode() == 200)
                {
                    $json = json_decode($response->getBody()->getContents(), true);

                    if (!empty($json['user']))
                    {
                        $userName = $json['user']['username'];

                        $client = app('discourse-client');
                        $response = $client->request(
                            'PUT',
                            "/u/{$userName}.json",
                            [
                                'form_params' => [
                                    'mailing_list_mode' => false,
                                    'mailing_list_mode_frequency' => 1,
                                    'email_digests' => false,
                                    'email_in_reply_to' => true,
                                    'email_messages_level' => 2,
                                    'email_level' => 2,
                                    'email_previous_replies' => 1,
                                    'digest_after_minutes' => 10080,
                                    'include_tl0_in_digests' => true
                                ]
                            ]
                        );

                        $worked = true;
                    }
                }

                if ($worked) {
                    Log::debug('Turned off Discourse mails for' . $user->id);
                } else {
                    Log::error(
                        'Could not turn off Discourse mails for user ' . $user->id . ': ' . $response->getReasonPhrase()
                    );
                }
            }
        }
    }

    public function setSetting($setting, $value)
    {
        Log::info("Set Discourse setting {$setting} to {$value}");

        $client = app('discourse-client');
        $response = $client->request(
            'PUT',
            "/admin/site_settings/{$setting}",
            [
                'form_params' => [
                    $setting => $value
                ]
            ]
        );

        Log::info("Set Discourse setting {$setting} to {$value}");
        Log::info('Response status: ' . $response->getStatusCode());
        Log::info($response->getBody());
    }

    public function anonymise($user)
    {
        Log::info("Anonymise {$user->id}");
        $client = app('discourse-client');

        // Get Discourse user id
        $endpoint = "/users/by-external/{$user->id}.json";

        $response = $client->request(
            'GET',
            $endpoint
        );

        $json = json_decode($response->getBody()->getContents(), true);
        if (empty($json['user'])) {
            throw new \Exception("User {$user->id} not found in Discourse");
        }

        $discourseUserId = $json['user']['id'];

        if ($discourseUserId) {
            $response = $client->request(
                'PUT',
                "/admin/users/$discourseUserId/anonymize.json"
            );

            $json = json_decode($response->getBody()->getContents(), true);

            if (empty($json['success'])) {
                throw new \Exception("Failed to anonymise user {$user->id}");
            }
        }

        Log::info('Response status: ' . $response->getStatusCode());
        Log::info($response->getBody());
    }
}
