<?php

namespace Tests\Feature;

use App\Group;
use App\Party;
use Tests\TestCase;

/**
 * Coverage for the F2-4/F2-5 deep-link redirectors that isn't already exercised elsewhere:
 *  - GET /user/reset, /user/recover: see PasswordResetTest, RecoverTest.
 *  - GET /group/invite/{code}, /party/invite/{code} (valid code): see
 *    InviteGroupTest::testInviteViaLink, InviteEventTest::testInviteViaLink.
 *  - GET /group/accept-invite, /party/accept-invite (valid hash, DB-effect assertions): see
 *    InviteGroupTest::testInvite, InviteEventTest::testInviteReal/testInvitableUserPOV/
 *    testInvitableNotifications.
 * This file covers the previously-untested GET /user/register/{hash?} redirector and the
 * invalid-hash branch of the two accept-invite redirectors.
 */
class RedirectorRoutesTest extends TestCase
{
    public function testRegisterRedirectsToFrontendWithoutHash(): void
    {
        $frontend = rtrim(config('restarters.frontend_url'), '/');

        $response = $this->get('/user/register');

        $response->assertRedirect($frontend.'/user/register');
    }

    public function testRegisterRedirectsToFrontendCarryingInviteHash(): void
    {
        $frontend = rtrim(config('restarters.frontend_url'), '/');

        $response = $this->get('/user/register/abc123hash');

        $response->assertRedirect($frontend.'/user/register?invite_hash=abc123hash');
    }

    public function testGroupAcceptInviteWithInvalidHashRedirectsWithoutMutatingAnything(): void
    {
        // /group/accept-invite/{id}/{hash} sits behind the 'auth', 'verifyUserConsent' group.
        $this->loginAsTestUser();

        $group = Group::factory()->create();
        $frontend = rtrim(config('restarters.frontend_url'), '/');

        $response = $this->get('/group/accept-invite/'.$group->idgroups.'/not-a-real-hash');

        $response->assertRedirect($frontend.'/group/view/'.$group->idgroups.'?invite=invalid');
        $this->assertDatabaseMissing('users_groups', [
            'group' => $group->idgroups,
            'status' => 'not-a-real-hash',
        ]);
    }

    public function testPartyAcceptInviteWithInvalidHashRedirectsWithoutMutatingAnything(): void
    {
        // /party/accept-invite/{id}/{hash} sits behind the 'auth', 'verifyUserConsent' group.
        $this->loginAsTestUser();

        $group = Group::factory()->create();
        $event = Party::factory()->create(['group' => $group->idgroups]);
        $frontend = rtrim(config('restarters.frontend_url'), '/');

        $response = $this->get('/party/accept-invite/'.$event->idevents.'/not-a-real-hash');

        $response->assertRedirect($frontend.'/party/view/'.$event->idevents.'?invite=invalid');
        $this->assertDatabaseMissing('events_users', [
            'event' => $event->idevents,
            'status' => 'not-a-real-hash',
        ]);
    }
}
