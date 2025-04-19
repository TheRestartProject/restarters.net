<?php

namespace Tests\Unit;

use App\Notifications\AdminAbnormalDevices;
use App\Notifications\AdminModerationEvent;
use App\Notifications\AdminModerationEventPhotos;
use App\Notifications\AdminModerationGroup;
use App\Notifications\AdminNewUser;
use App\Notifications\AdminUserDeleted;
use App\Notifications\AdminWordPressCreateEventFailure;
use App\Notifications\AdminWordPressCreateGroupFailure;
use App\Notifications\AdminWordPressEditEventFailure;
use App\Notifications\DeleteEventFromWordpressFailed;
use App\Notifications\EventConfirmed;
use App\Notifications\EventDevices;
use App\Notifications\EventRepairs;
use App\Notifications\GroupConfirmed;
use App\Notifications\JoinEvent;
use App\Notifications\JoinGroup;
use App\Notifications\NewDiscourseMember;
use App\Notifications\NewGroupMember;
use App\Notifications\NewGroupWithinRadius;
use App\Notifications\NotifyAdminNoDevices;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Notifications\ResetPassword;
use App\Notifications\RSVPEvent;
use App\User;
use DB;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    // These are all the notifications we test.
    private $classes = [
        AdminAbnormalDevices::class,
        AdminModerationEvent::class,
        AdminModerationEventPhotos::class,
        AdminModerationGroup::class,
        AdminNewUser::class,
        AdminUserDeleted::class,
        AdminWordPressCreateEventFailure::class,
        AdminWordPressCreateGroupFailure::class,
        AdminWordPressEditEventFailure::class,
        AdminWordPressCreateGroupFailure::class,
        DeleteEventFromWordpressFailed::class,
        EventDevices::class,
        EventRepairs::class,
        JoinGroup::class,
        NewDiscourseMember::class,
        NewGroupMember::class,
        NewGroupWithinRadius::class,
        NotifyAdminNoDevices::class,
        NotifyRestartersOfNewEvent::class,
        ResetPassword::class,
        RSVPEvent::class
    ];

    // These events are not currently tested in here because they have a different constructor which passes in
    // objects rather than arrays.
    //
    //        EventConfirmed::class
    //        GroupConfirmed::class
    //        JoinEvent::class

    // This is all the parameters any of the notifications need.
    private $params = [
        'event_name' => 'Event Name',
        'event_venue' => 'Event Venue',
        'event_url' => 'https://eventurl',
        'event_id' => 123,
        'event_start' => '2020/01/01',
        'group_name' => 'Group Name',
        'group_url' => 'https://groupurl',
        'group' => 'Group Name2',
        'name' => 'Name',
        'id' => 456,
        'message' => 'This is a message',
        'url' => 'https://someurl.com',
        'user_name' => 'User Name',
        'event_group' => 'Event Group',
    ];

    private $outputs = [];

    protected function setUp(): void {
        parent::setUp();

        // Create users with specific ids because the notification outputs have a link to the preferences which
        // includes the id, so we need it not to change.
        $this->useren = User::factory()->create(['language' => 'en', 'id' => 10001]);
        $this->userfr = User::factory()->create(['language' => 'fr', 'id' => 10002]);

        // This is the output pasted in from testGenerateOutputs.
        $this->outputs = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['subject'] = 'Abnormal number of miscellaneous devices';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['introLines'][0] = 'The event \'Event Venue\' has an abnormal number of miscellaneous devices. Please check the event and fix this issue.';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['actionText'] = 'View event';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['subject'] = 'Nombre anormal d\'appareils divers';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['introLines'][0] = 'L\'événement \':nom\' a un nombre anormal de périphériques divers. Veuillez vérifier l\'événement et résoudre ce problème.';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['array'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['array']['en']['title'] = 'Event has abnormal number of miscellaneous devices:';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['array']['en']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['array']['en']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['array']['fr']['title'] = 'L\'événement comporte un nombre anormal d\'appareils divers :';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['array']['fr']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminAbnormalDevices::class]['array']['fr']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEvent::class] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['subject'] = 'New event for Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['introLines'][0] = 'A new event has been created: \'Event Venue\'.';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['outroLines'][0] = 'This event might need your moderation, if your network moderates events and it hasn\'t yet been moderated by another administrator.';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['actionText'] = 'View event';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['subject'] = 'New event for Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['introLines'][0] = 'Un nouvel événement a été créé Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['outroLines'][0] = 'Cet événement peut nécessiter votre modération si votre réseau a modéré des événements et qu\'il n\'a pas encore été modéré par un autre administrateur.';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['array'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['array']['en']['title'] = 'New event for Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['array']['en']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['array']['en']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['array']['fr']['title'] = 'New event for Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['array']['fr']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEvent::class]['array']['fr']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['subject'] = 'New event photos uploaded to event: Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['introLines'][0] = 'Photos have been uploaded to an event: \'Event Venue\'.';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['outroLines'][0] = 'These photos might need your moderation, if they haven\'t yet been moderated by another administrator.';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['actionText'] = 'View event';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['subject'] = 'Nouvelles photos de l\'événement téléchargées sur l\'événement : Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['introLines'][0] = 'Des photos ont été téléchargées pour un événement: \'Event Venue\'.';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['outroLines'][0] = 'Ces photos peuvent nécessiter votre modération, si elles n\'ont pas encore été modérées par un autre administrateur.';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['en']['title'] = 'New event photos uploaded to event: :event';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['en']['event_id'] = 123;
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['en']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['en']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['fr']['title'] = 'Nouvelles photos d\'événement téléchargées sur l\'événement :name';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['fr']['event_id'] = 123;
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['fr']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminModerationEventPhotos::class]['array']['fr']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminModerationGroup::class] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['subject'] = 'New group created: Group Name';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['introLines'][0] = 'A new group has been created: \'Group Name\'.';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['outroLines'][0] = 'This group might need your moderation, if it hasn\'t yet been moderated by another administrator.';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['actionText'] = 'View group';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['actionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['en']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['subject'] = 'Nouveau Repair Café créé Group Name';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['introLines'][0] = 'Un nouveau Repair Café a été créé: \'Group Name\'.';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['outroLines'][0] = 'Ce Repair Café peut avoir besoin de votre modération s\'il n\'a pas encore été modéré par un autre administrateur.';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['actionText'] = 'View group';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['actionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['mail']['fr']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['array'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['array']['en']['title'] = 'New group created:';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['array']['en']['name'] = 'Group Name';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['array']['en']['url'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['array']['fr']['title'] = 'Nouveau Repair Café créé:';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['array']['fr']['name'] = 'Group Name';
        $this->outputs[\App\Notifications\AdminModerationGroup::class]['array']['fr']['url'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminNewUser::class] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['subject'] = 'New User Registration';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['introLines'][0] = 'A new user "Name" has joined the Restarters community.';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['actionText'] = 'View profile';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['actionUrl'] = 'http://restarters.test:8000/profile/456';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['en']['displayableActionUrl'] = 'http://restarters.test:8000/profile/456';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['subject'] = 'Enregistrement d\'un nouvel utilisateur';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['introLines'][0] = 'A new user "Name" has joined the Restarters community.';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['actionText'] = 'Voir profil';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['actionUrl'] = 'http://restarters.test:8000/profile/456';
        $this->outputs[\App\Notifications\AdminNewUser::class]['mail']['fr']['displayableActionUrl'] = 'http://restarters.test:8000/profile/456';
        $this->outputs[\App\Notifications\AdminNewUser::class]['array'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['array']['en']['title'] = 'New user has joined the community:';
        $this->outputs[\App\Notifications\AdminNewUser::class]['array']['en']['name'] = 'Name';
        $this->outputs[\App\Notifications\AdminNewUser::class]['array']['en']['url'] = 'http://restarters.test:8000/profile/456';
        $this->outputs[\App\Notifications\AdminNewUser::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\AdminNewUser::class]['array']['fr']['title'] = 'New user has joined the community:';
        $this->outputs[\App\Notifications\AdminNewUser::class]['array']['fr']['name'] = 'Name';
        $this->outputs[\App\Notifications\AdminNewUser::class]['array']['fr']['url'] = 'http://restarters.test:8000/profile/456';
        $this->outputs[\App\Notifications\AdminUserDeleted::class] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['subject'] = 'User Deleted';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['introLines'][0] = 'The user "Name" has deleted their Restarters account.';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['introLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['actionText'] = NULL;
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['actionUrl'] = NULL;
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['en']['displayableActionUrl'] = '';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['subject'] = 'Utilisateur Supprimé';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['introLines'][0] = 'The user "Name" has deleted their Restarters account.';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['introLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['actionText'] = NULL;
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['actionUrl'] = NULL;
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['mail']['fr']['displayableActionUrl'] = '';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['array'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['array']['en']['title'] = 'User has deleted their account:';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['array']['en']['name'] = 'Name';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['array']['fr']['title'] = 'User has deleted their account:';
        $this->outputs[\App\Notifications\AdminUserDeleted::class]['array']['fr']['name'] = 'Name';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['subject'] = 'Event WordPress failure';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['introLines'][0] = 'Event \'Event Venue\' failed to create a WordPress post during admin approval.';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['actionText'] = 'View event';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['subject'] = 'Échec de l\'événement WordPress';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['introLines'][0] = 'L\'événement \'Event Venue\' n\'a pas pu créer de publication WordPress lors de l\'approbation de l\'administrateur.';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['array'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['array']['en']['title'] = 'Event failed to create a new WordPress post:';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['array']['en']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['array']['en']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['array']['fr']['title'] = 'Échec de la création d\'un nouvel article WordPress:';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['array']['fr']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminWordPressCreateEventFailure::class]['array']['fr']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['subject'] = 'Group WordPress failure';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['introLines'][0] = 'Error creating group page for \'Group Name\' on WordPress.';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['actionText'] = 'View group';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['actionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['en']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['subject'] = 'Échec du Repair Café WordPress';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['introLines'][0] = 'Error creating group page for \'Group Name\' on WordPress.';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['actionText'] = 'Voir le Repair Café';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['actionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['mail']['fr']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['array'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['array']['en']['title'] = 'Group failed to create a new WordPress post:';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['array']['en']['name'] = 'Group Name';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['array']['en']['url'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['array']['fr']['title'] = 'Le Repair Café n\'a pas réussi à créer une nouvelle publication WordPress:';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['array']['fr']['name'] = 'Group Name';
        $this->outputs[\App\Notifications\AdminWordPressCreateGroupFailure::class]['array']['fr']['url'] = 'https://groupurl';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['subject'] = 'Event WordPress failure';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['introLines'][0] = 'Event \'Event Venue\' failed to post to WordPress during an edit to the event.';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['actionText'] = 'View event';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['subject'] = 'Échec de l\'événement WordPress';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['introLines'][0] = 'L\'événement \'Event Venue\' n\'a pas pu être publié sur WordPress lors d\'une modification de l\'événement.';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['array'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['array']['en']['title'] = 'Event failed to save to an existing WordPress post:';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['array']['en']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['array']['en']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['array']['fr']['title'] = 'Échec de l\'enregistrement de l\'événement dans un article WordPress existant:';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['array']['fr']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\AdminWordPressEditEventFailure::class]['array']['fr']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['subject'] = 'Failed to delete event from WordPress: Event Venue';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['introLines'][0] = 'Event deletion failed for Event Venue by Group Name.';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['introLines'][1] = '';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['introLines'][2] = 'Please find and delete this event manually from WordPress.';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['introLines'][3] = '';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['introLines'][4] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['actionText'] = NULL;
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['actionUrl'] = NULL;
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['en']['displayableActionUrl'] = '';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['subject'] = 'Échec de la suppression de l\'événement de WordPressEvent Venue';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['introLines'][0] = 'La suppression de l\'événement a échoué pour Event Venue par Group Name.';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['introLines'][1] = '';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['introLines'][2] = 'Veuillez rechercher et supprimer cet événement manuellement de WordPress.';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['introLines'][3] = '';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['introLines'][4] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['actionText'] = NULL;
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['actionUrl'] = NULL;
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['mail']['fr']['displayableActionUrl'] = '';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['array'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['array']['en']['title'] = 'Failed to delete event Event Venue by Group Name from WordPress';
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\DeleteEventFromWordpressFailed::class]['array']['fr']['title'] = 'Échec de la suppression de l\'événement Event Venue par Group Name de WordPress';
        $this->outputs[\App\Notifications\EventDevices::class] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['mail'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['subject'] = 'Contribute Devices';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['introLines'][0] = 'Thank you for hosting the event Event Venue, please help us outline what devices were bought to the event and the status of their repair. This will help us improve the quality of our data.';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['actionText'] = 'Contribute Data';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['subject'] = 'Contribuer aux dispositifs';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['introLines'][0] = 'Merci d\'avoir organisé l\'événement : aidez-nous à préciser quels appareils ont été amenés pour l\'événement et l\'état de leur réparation. Cela nous aidera à améliorer la qualité de nos données.';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['actionText'] = 'Contribuer aux données';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\EventDevices::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\EventDevices::class]['array'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['array']['en']['title'] = 'Contribute Devices';
        $this->outputs[\App\Notifications\EventDevices::class]['array']['en']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\EventDevices::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\EventDevices::class]['array']['fr']['title'] = 'Contribuer aux dispositifs';
        $this->outputs[\App\Notifications\EventDevices::class]['array']['fr']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\EventRepairs::class] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['mail'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['subject'] = 'Help us log repair info for Event Name';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['introLines'][0] = 'Thank you for fixing at the \':name\' event. The host has posted photos of any feedback left by participants and repair data. Please help us to improve the details of the repairs you carried out by adding any useful information or photos you have. Any extra details you can add will help future repair attempts.';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['actionText'] = 'Contribute repair info';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['subject'] = 'Aidez-nous à enregistrer les informations relatives à la réparation de Event Name';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['introLines'][0] = 'Merci d\'avoir réparé à l\'événement \':name\'. L\'hôte a mis en ligne des photos des commentaires laissés par les participants et des données sur les réparations. Veuillez nous aider à améliorer les détails des réparations que vous avez effectuées en ajoutant toute information utile ou toute photo dont vous disposez. Tous les détails supplémentaires que vous pouvez ajouter aideront les futures tentatives de réparation.';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['actionText'] = 'Contribuer aux informations sur les réparations';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\EventRepairs::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\EventRepairs::class]['array'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['array']['en']['title'] = 'Help us log repair info for Event Name';
        $this->outputs[\App\Notifications\EventRepairs::class]['array']['en']['name'] = 'Event Name';
        $this->outputs[\App\Notifications\EventRepairs::class]['array']['en']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\EventRepairs::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\EventRepairs::class]['array']['fr']['title'] = 'Aidez-nous à enregistrer les informations relatives à la réparation de Event Name';
        $this->outputs[\App\Notifications\EventRepairs::class]['array']['fr']['name'] = 'Event Name';
        $this->outputs[\App\Notifications\EventRepairs::class]['array']['fr']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\JoinGroup::class] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['mail'] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['subject'] = 'Invitation from Name to follow Group Name2';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['introLines'][0] = 'You have received this email because you have been invited by Name to follow the community repair group <b>Group Name2</b> on restarters.net.';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['introLines'][1] = '';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['introLines'][2] = 'Name attached this message with the invite:';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['introLines'][3] = '';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['introLines'][4] = '"This is a message"';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['introLines'][5] = '';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['outroLines'][0] = '';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['outroLines'][1] = 'If you think this invitation was not intended for you, please disregard this email.';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['actionText'] = 'Click to follow group';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['actionUrl'] = 'https://someurl.com';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['en']['displayableActionUrl'] = 'https://someurl.com';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['subject'] = 'Invitation de Name à suivre Group Name2';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['introLines'][0] = 'Vous avez reçu cet e-mail car vous avez été invité par Name à suivre leRepair Café <b>Group Name2</b> sur restarters.net.';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['introLines'][1] = '';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['introLines'][2] = 'Name joint ce message avec l\'invitation :';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['introLines'][3] = '';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['introLines'][4] = '"This is a message"';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['introLines'][5] = '';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['outroLines'][0] = '';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['outroLines'][1] = 'Si vous pensez que cette invitation ne vous est pas destinée, veuillez ne pas tenir compte de cet e-mail.';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['actionText'] = 'Cliquez pour suivre le Repair Café';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['actionUrl'] = 'https://someurl.com';
        $this->outputs[\App\Notifications\JoinGroup::class]['mail']['fr']['displayableActionUrl'] = 'https://someurl.com';
        $this->outputs[\App\Notifications\JoinGroup::class]['array'] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\JoinGroup::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['subject'] = 'Welcome to Group Name';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['introLines'][0] = 'Thank you for following Group Name! You will now receive notifications when new events are planned and will be added to group messages. <a href="https://talk.restarters.net/t/how-to-communicate-with-your-repair-group/6293">Learn how group messages work and how to change your notification settings</a>.';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['introLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['actionText'] = NULL;
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['actionUrl'] = NULL;
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['en']['displayableActionUrl'] = '';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['subject'] = 'Welcome to Group Name';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['introLines'][0] = 'Thank you for following Group Name! You will now receive notifications when new events are planned and will be added to group messages. <a href="https://talk.restarters.net/t/how-to-communicate-with-your-repair-group/6293">Learn how group messages work and how to change your notification settings</a>.';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['introLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['actionText'] = NULL;
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['actionUrl'] = NULL;
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['mail']['fr']['displayableActionUrl'] = '';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['array'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['array']['en']['title'] = 'Welcome to Group Name';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['array']['en']['group_name'] = 'Group Name';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['array']['fr']['title'] = 'Welcome to Group Name';
        $this->outputs[\App\Notifications\NewDiscourseMember::class]['array']['fr']['group_name'] = 'Group Name';
        $this->outputs[\App\Notifications\NewGroupMember::class] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['subject'] = 'New group member followed Group Name';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['introLines'][0] = 'A new volunteer, User Name, has followed your group \'Group Name\'.';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['actionText'] = 'Go to group';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['actionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['en']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['subject'] = 'Nouveau membre du Repair Café suivi Group Name';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['introLines'][0] = 'Un nouveau volontaire, User Name, a suivi votre Repair Café \'Group Name\'.';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['actionText'] = 'Aller au Repair Café';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['actionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupMember::class]['mail']['fr']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupMember::class]['array'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['array']['en']['title'] = 'A new volunteer, User Name, has followed ';
        $this->outputs[\App\Notifications\NewGroupMember::class]['array']['en']['name'] = 'Group Name';
        $this->outputs[\App\Notifications\NewGroupMember::class]['array']['en']['url'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupMember::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\NewGroupMember::class]['array']['fr']['title'] = 'Un nouveau volontaire, User Name, a suivi';
        $this->outputs[\App\Notifications\NewGroupMember::class]['array']['fr']['name'] = 'Group Name';
        $this->outputs[\App\Notifications\NewGroupMember::class]['array']['fr']['url'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['subject'] = 'There\'s a new repair group near to you';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['introLines'][0] = 'A new group near to you, Group Name, has just become active on Restarters.net.';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['actionText'] = 'Find out more about Group Name';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['actionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['en']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['subject'] = 'Il y a un nouveau Repair Café près de chez vous.';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['introLines'][0] = 'Un nouveau Repair Café près de chez vous, Group Name, vient de devenir actif sur Restarters.net.';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['actionText'] = 'En savoir plus sur Group Name';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['actionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['mail']['fr']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['array'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['array']['en']['title'] = 'A new repair group near you:';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['array']['en']['name'] = 'Group Name';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['array']['en']['url'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['array']['fr']['title'] = 'Un nouveau Repair Café près de chez vous :';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['array']['fr']['name'] = 'Group Name';
        $this->outputs[\App\Notifications\NewGroupWithinRadius::class]['array']['fr']['url'] = 'https://groupurl';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['subject'] = 'Recent event with no devices added';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['introLines'][0] = 'Your moderation is needed for \'Event Venue\'.';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['introLines'][1] = 'No devices have been added against this event.';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['actionText'] = 'View event';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['subject'] = 'Événement récent sans appareil ajouté';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['introLines'][0] = 'Votre modération est nécessaire pour \'Event Venue\'.';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['introLines'][1] = 'Aucun appareil n\'a été ajouté pour cet événement.';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['array'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['array']['en']['title'] = 'Moderation needed on event with no devices:';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['array']['en']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['array']['en']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['array']['fr']['title'] = 'Modération nécessaire pour un événement sans appareil:';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['array']['fr']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\NotifyAdminNoDevices::class]['array']['fr']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['subject'] = 'New event for Event Group at 2020/01/01';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['introLines'][0] = 'There has been a new event added to your group: \'Event Venue\'.';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['actionText'] = 'View event';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['subject'] = 'Nouvel événement pour Event Group le 2020/01/01';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['introLines'][0] = 'Un nouvel événement a été ajouté à votre Repair Café : \'Event Venue\'.';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['array'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['array']['en']['title'] = 'A new event has been created for group Event Group:';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['array']['en']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['array']['en']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['array']['fr']['title'] = 'Un nouvel événement a été créé pour le Repair Café Event Group :';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['array']['fr']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\NotifyRestartersOfNewEvent::class]['array']['fr']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\ResetPassword::class] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['mail'] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['subject'] = 'Reset password';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['introLines'][0] = 'You are receiving this email because we received a password reset request for your account.';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['outroLines'][0] = 'If you did not request a password reset, no further action is required.';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['actionText'] = 'Reset password';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['actionUrl'] = 'https://someurl.com&locale=en';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['en']['displayableActionUrl'] = 'https://someurl.com&locale=en';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['subject'] = 'Réinitialiser le mot de passe';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['introLines'][0] = 'Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation du mot de passe de votre compte.';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['outroLines'][0] = 'Si vous n\'avez pas demandé de réinitialisation du mot de passe, aucune action supplémentaire n\'est requise.';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['actionText'] = 'Réinitialiser le mot de passe';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['actionUrl'] = 'https://someurl.com&locale=fr';
        $this->outputs[\App\Notifications\ResetPassword::class]['mail']['fr']['displayableActionUrl'] = 'https://someurl.com&locale=fr';
        $this->outputs[\App\Notifications\ResetPassword::class]['array'] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\ResetPassword::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['level'] = 'info';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['subject'] = 'User Name has RSVPed to your event';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['greeting'] = 'Hello!';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['salutation'] = NULL;
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['introLines'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['introLines'][0] = 'A volunteer, User Name, has RSVPed to the \'Event Venue\' event.';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['outroLines'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['actionText'] = 'View your event';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['level'] = 'info';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['subject'] = 'User Name a répondu à l\'invitation à votre événement';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['salutation'] = NULL;
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['introLines'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['introLines'][0] = 'Un volontaire, User Name, s\'est inscrit à l\'événement \'Event Venue\'.';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['outroLines'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['actionText'] = 'Voir votre événement';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\RSVPEvent::class]['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs[\App\Notifications\RSVPEvent::class]['array'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['array']['en'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['array']['en']['title'] = ': has RSVPed to your event:';
        $this->outputs[\App\Notifications\RSVPEvent::class]['array']['en']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\RSVPEvent::class]['array']['en']['url'] = 'https://eventurl';
        $this->outputs[\App\Notifications\RSVPEvent::class]['array']['fr'] = [];
        $this->outputs[\App\Notifications\RSVPEvent::class]['array']['fr']['title'] = 'User Name a répondu à l\'invitation à votre événement :';
        $this->outputs[\App\Notifications\RSVPEvent::class]['array']['fr']['name'] = 'Event Venue';
        $this->outputs[\App\Notifications\RSVPEvent::class]['array']['fr']['url'] = 'https://eventurl';
    }

    // Generate all the actual outputs.  Useful when maintaining this test.
    public function testGenerateOutputs(): void
    {
        foreach ($this->classes as $class)
        {
            $notificationen = new $class($this->params, $this->useren);

            $outputs[$class]['mail']['en'] = $notificationen->toMail($this->useren)->toArray();
            $outputs[$class]['array']['en'] = $notificationen->toArray($this->useren);

            $notificationfr = new $class($this->params, $this->userfr);
            $outputs[$class]['mail']['fr'] = $notificationfr->toMail($this->userfr)->toArray();
            $outputs[$class]['array']['fr'] = $notificationfr->toArray($this->userfr);
        }

//        $this->recursive_print('$this->outputs', $outputs);

        $this->assertTrue(true);
    }

    function recursive_print($varname, $varval)
    {
        if (!is_array($varval)):
            print $varname . ' = ' . var_export($varval, true) . ";\n";
        else:
            print $varname . " = [];\n";
            foreach ($varval as $key => $val):
                $this->recursive_print($varname . "[" . var_export($key, true) . "]", $val);
            endforeach;
        endif;
    }

    // Test all the notifications generate the expected outputs.
    public function testCompareOutputs(): void
    {
        foreach ($this->classes as $class)
        {
            $notificationen = new $class($this->params, $this->useren);

            $this->assertEquals($this->outputs[$class]['mail']['en'], $notificationen->toMail($this->useren)->toArray(), $class);
            $this->assertEquals($this->outputs[$class]['array']['en'], $notificationen->toArray($this->useren), $class);

            $notificationfr = new $class($this->params, $this->userfr);
            $this->assertEquals($this->outputs[$class]['mail']['fr'], $notificationfr->toMail($this->userfr)->toArray());
            $this->assertEquals($this->outputs[$class]['array']['fr'], $notificationfr->toArray($this->userfr));
        }
    }

}
