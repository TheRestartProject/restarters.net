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

    protected function setup(): void {
        parent::setUp();

        // Create users with specific ids because the notification outputs have a link to the preferences which
        // includes the id, so we need it not to change.
        $this->useren = factory(User::class)->create(['language' => 'en', 'id' => 10001]);
        $this->userfr = factory(User::class)->create(['language' => 'fr', 'id' => 10002]);

        // This is the output pasted in from testGenerateOutputs.
        $this->outputs = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['subject'] = 'Abnormal number of miscellaneous devices';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['introLines'][0] = 'The event \'Event Venue\' has an abnormal number of miscellaneous devices. Please check the event and fix this issue.';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['actionText'] = 'View event';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['subject'] = 'Nombre anormal d\'appareils divers';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['introLines'][0] = 'L\'événement \':nom\' a un nombre anormal de périphériques divers. Veuillez vérifier l\'événement et résoudre ce problème.';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['array'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['array']['en'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['array']['en']['title'] = 'Event has abnormal number of miscellaneous devices:';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['array']['en']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['array']['en']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['array']['fr']['title'] = 'L\'événement comporte un nombre anormal d\'appareils divers :';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['array']['fr']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminAbnormalDevices']['array']['fr']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEvent'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['subject'] = 'New event for Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['introLines'][0] = 'A new event has been created: \'Event Venue\'.';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['outroLines'][0] = 'This event might need your moderation, if your network moderates events and it hasn\'t yet been moderated by another administrator.';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['actionText'] = 'View event';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['subject'] = 'New event for Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['introLines'][0] = 'Un nouvel événement a été créé Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['outroLines'][0] = 'Cet événement peut nécessiter votre modération si votre réseau a modéré des événements et qu\'il n\'a pas encore été modéré par un autre administrateur.';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['array'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['array']['en'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['array']['en']['title'] = 'New event for Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['array']['en']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['array']['en']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEvent']['array']['fr']['title'] = 'New event for Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['array']['fr']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEvent']['array']['fr']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['subject'] = 'New event photos uploaded to event: Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['introLines'][0] = 'Photos have been uploaded to an event: \'Event Venue\'.';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['outroLines'][0] = 'These photos might need your moderation, if they haven\'t yet been moderated by another administrator.';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['actionText'] = 'View event';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['subject'] = 'Nouvelles photos de l\'événement téléchargées sur l\'événement : Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['introLines'][0] = 'Des photos ont été téléchargées pour un événement: \'Event Venue\'.';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['outroLines'][0] = 'Ces photos peuvent nécessiter votre modération, si elles n\'ont pas encore été modérées par un autre administrateur.';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['en'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['en']['title'] = 'New event photos uploaded to event: :event';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['en']['event_id'] = 123;
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['en']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['en']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['fr']['title'] = 'Nouvelles photos d\'événement téléchargées sur l\'événement :name';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['fr']['event_id'] = 123;
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['fr']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminModerationEventPhotos']['array']['fr']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminModerationGroup'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['subject'] = 'New group created: Group Name';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['introLines'][0] = 'A new group has been created: \'Group Name\'.';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['outroLines'][0] = 'This group might need your moderation, if it hasn\'t yet been moderated by another administrator.';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['actionText'] = 'View group';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['actionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['en']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['subject'] = 'Nouveau Repair Café créé Group Name';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['introLines'][0] = 'Un nouveau Repair Café a été créé: \'Group Name\'.';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['outroLines'][0] = 'Ce Repair Café peut avoir besoin de votre modération s\'il n\'a pas encore été modéré par un autre administrateur.';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['outroLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['actionText'] = 'View group';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['actionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['mail']['fr']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['array'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['array']['en'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['array']['en']['title'] = 'New group created:';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['array']['en']['name'] = 'Group Name';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['array']['en']['url'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminModerationGroup']['array']['fr']['title'] = 'Nouveau Repair Café créé:';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['array']['fr']['name'] = 'Group Name';
        $this->outputs['App\\Notifications\\AdminModerationGroup']['array']['fr']['url'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminNewUser'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['mail'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['subject'] = 'New User Registration';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['introLines'][0] = 'A new user "Name" has joined the Restarters community.';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['actionText'] = 'View profile';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['actionUrl'] = 'http://restarters.test:8000/profile/456';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['en']['displayableActionUrl'] = 'http://restarters.test:8000/profile/456';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['subject'] = 'Enregistrement d\'un nouvel utilisateur';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['introLines'][0] = 'A new user "Name" has joined the Restarters community.';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['actionText'] = 'View profile';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['actionUrl'] = 'http://restarters.test:8000/profile/456';
        $this->outputs['App\\Notifications\\AdminNewUser']['mail']['fr']['displayableActionUrl'] = 'http://restarters.test:8000/profile/456';
        $this->outputs['App\\Notifications\\AdminNewUser']['array'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['array']['en'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['array']['en']['title'] = 'New user has joined the community:';
        $this->outputs['App\\Notifications\\AdminNewUser']['array']['en']['name'] = 'Name';
        $this->outputs['App\\Notifications\\AdminNewUser']['array']['en']['url'] = 'http://restarters.test:8000/profile/456';
        $this->outputs['App\\Notifications\\AdminNewUser']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminNewUser']['array']['fr']['title'] = 'New user has joined the community:';
        $this->outputs['App\\Notifications\\AdminNewUser']['array']['fr']['name'] = 'Name';
        $this->outputs['App\\Notifications\\AdminNewUser']['array']['fr']['url'] = 'http://restarters.test:8000/profile/456';
        $this->outputs['App\\Notifications\\AdminUserDeleted'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['subject'] = 'User Deleted';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['introLines'][0] = 'The user "Name" has deleted their Restarters account.';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['introLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['actionText'] = NULL;
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['actionUrl'] = NULL;
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['en']['displayableActionUrl'] = '';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['subject'] = 'Utilisateur Supprimé';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['introLines'][0] = 'The user "Name" has deleted their Restarters account.';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['introLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['actionText'] = NULL;
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['actionUrl'] = NULL;
        $this->outputs['App\\Notifications\\AdminUserDeleted']['mail']['fr']['displayableActionUrl'] = '';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['array'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['array']['en'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['array']['en']['title'] = 'User has deleted their account:';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['array']['en']['name'] = 'Name';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminUserDeleted']['array']['fr']['title'] = 'User has deleted their account:';
        $this->outputs['App\\Notifications\\AdminUserDeleted']['array']['fr']['name'] = 'Name';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['subject'] = 'Event WordPress failure';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['introLines'][0] = 'Event \'Event Venue\' failed to create a WordPress post during admin approval.';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['actionText'] = 'View event';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['subject'] = 'Échec de l\'événement WordPress';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['introLines'][0] = 'L\'événement \'Event Venue\' n\'a pas pu créer de publication WordPress lors de l\'approbation de l\'administrateur.';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['array'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['array']['en'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['array']['en']['title'] = 'Event failed to create a new WordPress post:';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['array']['en']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['array']['en']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['array']['fr']['title'] = 'Échec de la création d\'un nouvel article WordPress:';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['array']['fr']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminWordPressCreateEventFailure']['array']['fr']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['subject'] = 'Group WordPress failure';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['introLines'][0] = 'Error creating group page for \'Group Name\' on WordPress.';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['actionText'] = 'View group';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['actionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['en']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['subject'] = 'Échec du Repair Café WordPress';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['introLines'][0] = 'Error creating group page for \'Group Name\' on WordPress.';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['actionText'] = 'Voir le Repair Café';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['actionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['mail']['fr']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['array'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['array']['en'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['array']['en']['title'] = 'Group failed to create a new WordPress post:';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['array']['en']['name'] = 'Group Name';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['array']['en']['url'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['array']['fr']['title'] = 'Le Repair Café n\'a pas réussi à créer une nouvelle publication WordPress:';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['array']['fr']['name'] = 'Group Name';
        $this->outputs['App\\Notifications\\AdminWordPressCreateGroupFailure']['array']['fr']['url'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['subject'] = 'Event WordPress failure';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['introLines'][0] = 'Event \'Event Venue\' failed to post to WordPress during an edit to the event.';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['actionText'] = 'View event';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['subject'] = 'Échec de l\'événement WordPress';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['introLines'][0] = 'L\'événement \'Event Venue\' n\'a pas pu être publié sur WordPress lors d\'une modification de l\'événement.';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['array'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['array']['en'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['array']['en']['title'] = 'Event failed to save to an existing WordPress post:';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['array']['en']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['array']['en']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['array']['fr']['title'] = 'Échec de l\'enregistrement de l\'événement dans un article WordPress existant:';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['array']['fr']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\AdminWordPressEditEventFailure']['array']['fr']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['subject'] = 'Failed to delete event from WordPress: Event Venue';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['introLines'][0] = 'Event deletion failed for Event Venue by Group Name.';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['introLines'][1] = '';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['introLines'][2] = 'Please find and delete this event manually from WordPress.';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['introLines'][3] = '';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['introLines'][4] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['actionText'] = NULL;
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['actionUrl'] = NULL;
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['en']['displayableActionUrl'] = '';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['subject'] = 'Échec de la suppression de l\'événement de WordPressEvent Venue';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['introLines'][0] = 'La suppression de l\'événement a échoué pour Event Venue par Group Name.';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['introLines'][1] = '';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['introLines'][2] = 'Veuillez rechercher et supprimer cet événement manuellement de WordPress.';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['introLines'][3] = '';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['introLines'][4] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['actionText'] = NULL;
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['actionUrl'] = NULL;
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['mail']['fr']['displayableActionUrl'] = '';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['array'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['array']['en'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['array']['en']['title'] = 'Failed to delete event Event Venue by Group Name from WordPress';
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\DeleteEventFromWordpressFailed']['array']['fr']['title'] = 'Échec de la suppression de l\'événement Event Venue par Group Name de WordPress';
        $this->outputs['App\\Notifications\\EventDevices'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['mail'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['subject'] = 'Contribute Devices';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['introLines'][0] = 'Thank you for hosting the event Event Venue, please help us outline what devices were bought to the event and the status of their repair. This will help us improve the quality of our data.';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['actionText'] = 'Contribute Data';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['subject'] = 'Contribuer aux dispositifs';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['introLines'][0] = 'Merci d\'avoir organisé l\'événement : aidez-nous à préciser quels appareils ont été amenés pour l\'événement et l\'état de leur réparation. Cela nous aidera à améliorer la qualité de nos données.';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['actionText'] = 'Contribuer aux données';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\EventDevices']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\EventDevices']['array'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['array']['en'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['array']['en']['title'] = 'Contribute Devices';
        $this->outputs['App\\Notifications\\EventDevices']['array']['en']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\EventDevices']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\EventDevices']['array']['fr']['title'] = 'Contribuer aux dispositifs';
        $this->outputs['App\\Notifications\\EventDevices']['array']['fr']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\EventRepairs'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['mail'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['subject'] = 'Help us log repair info for Event Name';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['introLines'][0] = 'Thank you for fixing at the \':name\' event. The host has posted photos of any feedback left by participants and repair data. Please help us to improve the details of the repairs you carried out by adding any useful information or photos you have. Any extra details you can add will help future repair attempts.';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['actionText'] = 'Contribute repair info';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['subject'] = 'Aidez-nous à enregistrer les informations relatives à la réparation de Event Name';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['introLines'][0] = 'Merci d\'avoir réparé à l\'événement \':name\'. L\'hôte a mis en ligne des photos des commentaires laissés par les participants et des données sur les réparations. Veuillez nous aider à améliorer les détails des réparations que vous avez effectuées en ajoutant toute information utile ou toute photo dont vous disposez. Tous les détails supplémentaires que vous pouvez ajouter aideront les futures tentatives de réparation.';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['actionText'] = 'Contribuer aux informations sur les réparations';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\EventRepairs']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\EventRepairs']['array'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['array']['en'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['array']['en']['title'] = 'Help us log repair info for Event Name';
        $this->outputs['App\\Notifications\\EventRepairs']['array']['en']['name'] = 'Event Name';
        $this->outputs['App\\Notifications\\EventRepairs']['array']['en']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\EventRepairs']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\EventRepairs']['array']['fr']['title'] = 'Aidez-nous à enregistrer les informations relatives à la réparation de Event Name';
        $this->outputs['App\\Notifications\\EventRepairs']['array']['fr']['name'] = 'Event Name';
        $this->outputs['App\\Notifications\\EventRepairs']['array']['fr']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\JoinGroup'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['mail'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['subject'] = 'Invitation from Name to follow Group Name2';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['introLines'][0] = 'You have received this email because you have been invited by Name to follow the community repair group <b>Group Name2</b> on restarters.net.';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['introLines'][1] = '';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['introLines'][2] = 'Name attached this message with the invite:';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['introLines'][3] = '';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['introLines'][4] = '"This is a message"';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['introLines'][5] = '';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['outroLines'][0] = '';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['outroLines'][1] = 'If you think this invitation was not intended for you, please disregard this email.';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['actionText'] = 'Click to follow group';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['actionUrl'] = 'https://someurl.com';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['en']['displayableActionUrl'] = 'https://someurl.com';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['subject'] = 'Invitation de Name à suivre Group Name2';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['introLines'][0] = 'Vous avez reçu cet e-mail car vous avez été invité par Name à suivre leRepair Café <b>Group Name2</b> sur restarters.net.';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['introLines'][1] = '';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['introLines'][2] = 'Name joint ce message avec l\'invitation :';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['introLines'][3] = '';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['introLines'][4] = '"This is a message"';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['introLines'][5] = '';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['outroLines'][0] = '';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['outroLines'][1] = 'Si vous pensez que cette invitation ne vous est pas destinée, veuillez ne pas tenir compte de cet e-mail.';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['actionText'] = 'Cliquez pour suivre le Repair Café';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['actionUrl'] = 'https://someurl.com';
        $this->outputs['App\\Notifications\\JoinGroup']['mail']['fr']['displayableActionUrl'] = 'https://someurl.com';
        $this->outputs['App\\Notifications\\JoinGroup']['array'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['array']['en'] = [];
        $this->outputs['App\\Notifications\\JoinGroup']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['subject'] = 'Welcome to Group Name';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['introLines'][0] = 'Thank you for following Group Name! You will now receive notifications when new events are planned and will be added to group messages. <a href="https://talk.restarters.net/t/how-to-communicate-with-your-repair-group/6293">Learn how group messages work and how to change your notification settings</a>.';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['introLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['actionText'] = NULL;
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['actionUrl'] = NULL;
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['en']['displayableActionUrl'] = '';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['subject'] = 'Welcome to Group Name';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['introLines'][0] = 'Thank you for following Group Name! You will now receive notifications when new events are planned and will be added to group messages. <a href="https://talk.restarters.net/t/how-to-communicate-with-your-repair-group/6293">Learn how group messages work and how to change your notification settings</a>.';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['introLines'][1] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['actionText'] = NULL;
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['actionUrl'] = NULL;
        $this->outputs['App\\Notifications\\NewDiscourseMember']['mail']['fr']['displayableActionUrl'] = '';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['array'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['array']['en'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['array']['en']['title'] = 'Welcome to Group Name';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['array']['en']['group_name'] = 'Group Name';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\NewDiscourseMember']['array']['fr']['title'] = 'Welcome to Group Name';
        $this->outputs['App\\Notifications\\NewDiscourseMember']['array']['fr']['group_name'] = 'Group Name';
        $this->outputs['App\\Notifications\\NewGroupMember'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['mail'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['subject'] = 'New group member followed Group Name';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['introLines'][0] = 'A new volunteer, User Name, has followed your group \'Group Name\'.';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['actionText'] = 'Go to group';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['actionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['en']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['subject'] = 'Nouveau membre du Repair Café suivi Group Name';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['introLines'][0] = 'Un nouveau volontaire, User Name, a suivi votre Repair Café \'Group Name\'.';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['actionText'] = 'Aller au Repair Café';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['actionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupMember']['mail']['fr']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupMember']['array'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['array']['en'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['array']['en']['title'] = 'A new volunteer, User Name, has followed ';
        $this->outputs['App\\Notifications\\NewGroupMember']['array']['en']['name'] = 'Group Name';
        $this->outputs['App\\Notifications\\NewGroupMember']['array']['en']['url'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupMember']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\NewGroupMember']['array']['fr']['title'] = 'Un nouveau volontaire, User Name, a suivi';
        $this->outputs['App\\Notifications\\NewGroupMember']['array']['fr']['name'] = 'Group Name';
        $this->outputs['App\\Notifications\\NewGroupMember']['array']['fr']['url'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['subject'] = 'There\'s a new repair group near to you';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['introLines'][0] = 'A new group near to you, Group Name, has just become active on Restarters.net.';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['actionText'] = 'Find out more about Group Name';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['actionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['en']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['subject'] = 'Il y a un nouveau Repair Café près de chez vous.';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['introLines'][0] = 'Un nouveau Repair Café près de chez vous, Group Name, vient de devenir actif sur Restarters.net.';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['actionText'] = 'En savoir plus sur Group Name';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['actionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['mail']['fr']['displayableActionUrl'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['array'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['array']['en'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['array']['en']['title'] = 'A new repair group near you:';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['array']['en']['name'] = 'Group Name';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['array']['en']['url'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['array']['fr']['title'] = 'Un nouveau Repair Café près de chez vous :';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['array']['fr']['name'] = 'Group Name';
        $this->outputs['App\\Notifications\\NewGroupWithinRadius']['array']['fr']['url'] = 'https://groupurl';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['subject'] = 'Recent event with no devices added';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['introLines'][0] = 'Your moderation is needed for \'Event Venue\'.';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['introLines'][1] = 'No devices have been added against this event.';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['actionText'] = 'View event';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['subject'] = 'Événement récent sans appareil ajouté';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['introLines'][0] = 'Votre modération est nécessaire pour \'Event Venue\'.';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['introLines'][1] = 'Aucun appareil n\'a été ajouté pour cet événement.';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['array'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['array']['en'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['array']['en']['title'] = 'Moderation needed on event with no devices:';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['array']['en']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['array']['en']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['array']['fr']['title'] = 'Modération nécessaire pour un événement sans appareil:';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['array']['fr']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\NotifyAdminNoDevices']['array']['fr']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['subject'] = 'New event for Event Group';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['introLines'][0] = 'There has been a new event added to your group: \'Event Venue\'.';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['actionText'] = 'View event';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['subject'] = 'Nouvel événement pour Event Group';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['introLines'][0] = 'Un nouvel événement a été ajouté à votre Repair Café : \'Event Venue\'.';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['outroLines'][0] = 'Si vous souhaitez ne plus recevoir ces courriels, veuillez consulter <a href="http://restarters.test:8000/user/edit/10002">vos préférences</a> sur votre compte.';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['actionText'] = 'Voir l\'événement';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['array'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['array']['en'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['array']['en']['title'] = 'A new event has been created for group Event Group:';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['array']['en']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['array']['en']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['array']['fr']['title'] = 'Un nouvel événement a été créé pour le Repair Café Event Group :';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['array']['fr']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\NotifyRestartersOfNewEvent']['array']['fr']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\ResetPassword'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['mail'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['subject'] = 'Reset password';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['introLines'][0] = 'You are receiving this email because we received a password reset request for your account.';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['outroLines'][0] = 'If you did not request a password reset, no further action is required.';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['actionText'] = 'Reset password';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['actionUrl'] = 'https://someurl.com';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['en']['displayableActionUrl'] = 'https://someurl.com';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['subject'] = 'Réinitialiser le mot de passe';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['introLines'][0] = 'Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation du mot de passe de votre compte.';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['outroLines'][0] = 'Si vous n\'avez pas demandé de réinitialisation du mot de passe, aucune action supplémentaire n\'est requise.';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['actionText'] = 'Réinitialiser le mot de passe';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['actionUrl'] = 'https://someurl.com';
        $this->outputs['App\\Notifications\\ResetPassword']['mail']['fr']['displayableActionUrl'] = 'https://someurl.com';
        $this->outputs['App\\Notifications\\ResetPassword']['array'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['array']['en'] = [];
        $this->outputs['App\\Notifications\\ResetPassword']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['mail'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['level'] = 'info';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['subject'] = 'User Name has RSVPed to your event';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['greeting'] = 'Hello!';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['introLines'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['introLines'][0] = 'A volunteer, User Name, has RSVPed to the \'Event Venue\' event.';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['outroLines'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10001#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['actionText'] = 'View your event';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['en']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['level'] = 'info';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['subject'] = 'User Name a répondu à l\'invitation à votre événement';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['greeting'] = 'Bonjour !';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['salutation'] = NULL;
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['introLines'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['introLines'][0] = 'Un volontaire, User Name, s\'est inscrit à l\'événement \'Event Venue\'.';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['outroLines'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['outroLines'][0] = 'If you would like to stop receiving these emails, please visit <a href="http://restarters.test:8000/user/edit/10002#list-email-preferences">your preferences</a> on your account.';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['actionText'] = 'Voir votre événement';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['actionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\RSVPEvent']['mail']['fr']['displayableActionUrl'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\RSVPEvent']['array'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['array']['en'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['array']['en']['title'] = ': has RSVPed to your event:';
        $this->outputs['App\\Notifications\\RSVPEvent']['array']['en']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\RSVPEvent']['array']['en']['url'] = 'https://eventurl';
        $this->outputs['App\\Notifications\\RSVPEvent']['array']['fr'] = [];
        $this->outputs['App\\Notifications\\RSVPEvent']['array']['fr']['title'] = 'User Name a répondu à l\'invitation à votre événement :';
        $this->outputs['App\\Notifications\\RSVPEvent']['array']['fr']['name'] = 'Event Venue';
        $this->outputs['App\\Notifications\\RSVPEvent']['array']['fr']['url'] = 'https://eventurl';
    }

    // Generate all the actual outputs.  Useful when maintaining this test.
    public function testGenerateOutputs()
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
    public function testCompareOutputs()
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
