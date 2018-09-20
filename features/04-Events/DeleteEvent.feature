Feature: Delete Event

In order to keep the list of events tidy,
As a host or an admin
I want to be able to delete events that should not be in the application

The simplest use case is deleting events that have not been moderated, and
do not have any volunteers attached to them.  Either as RSVP or invitation.

We generally do not want to delete events that volunteers have been invited
to or are attending.  

We do not want to delete events that have device data associated with them.
It's possible there will be some rare cases that we need to do that as an
Administrator, but it should only be an administrator level action.  As it
should only be in very rare cases, for now it is left as such that the only
way to do it is to delete all devices, remove all people associated with the
event, and then delete.  If it ever became a regularly required thing, we could
add a single button to do that for Admins, but this is unlikely.

To keep it simple for now, we could just only allow deletion when no volunteers
are associated.  And you need to remove yourself from the event?  It makes it a
lot easier to implement, however, it doesn't make much sense from a user perspective.

# Fail
# Doesn't display a notification message on return to list of events
Scenario: Unmoderated event deleted successfully
  When I successfully delete an event
  Then I am returned to the list of events
  And the event is no longer displayed in the application
  And I see a message saying 'Event successfully deleted'

# Fail
# From the code, doesn't look like it will remove the event from therestartproject.org
# Could this be confirmed?
Scenario: Moderated event deleted successfully
  When I successfully delete an event
  Then the event is removed from the list of events
  And I am returned to the list of events
  And I see a message saying 'Event successfully deleted'
  And the event is removed from the list of events on therestartproject.org

# Pass 
Scenario: Admin tries to delete event they did not create, with no volunteers associated
  Given I am an administrator
  And I am viewing an event that I did not create
  And the event has with no volunteers associated
  When I press the delete event button
  Then I am allowed to delete the event

# Pass
Scenario: Admin tries to delete event they did not create, with some volunteers associated
  Given I am an administrator
  And I am viewing an event that I did not create
  And the event has some volunteers associated
  When I press the delete event button
  Then I am not allowed to delete the event
  And I am shown a message saying 'Sorry, you cannot delete this event while there are volunteers associated.'

# Fail
# They are shown the message 'Sorry you cannot delete this event as you have invited other volunteers'
Scenario: Admin tries to delete event they created, with no other volunteers associated
  Given I am an administrator
  And I am viewing an event that I created
  And the event has only myself associated
  When I press the delete event button
  Then I am allowed to delete the event

# Pass
Scenario: Admin tries to delete event they created, with other volunteers associated
  Given I am an administrator
  And I am viewing an event that I created
  And the event has other volunteers associated
  When I press the delete event button
  Then I am not allowed to delete the event
  And I am shown a message saying 'Sorry, you cannot delete this event while there are volunteers associated.'

# Fail
# Two issues
# Have to remove the myself first - otherwise shown message saying I have invited others
# After doing that, I am correctly shown message saying 'Are you sure you want to delete this event?'
# However when I click OK, I get the message 'You do not have permission to delete this event'
Scenario: Host tries to delete event they created, with no other volunteers associated
  Given I am a host
  And I am viewing an event that I created
  And the event has with no volunteers associated (I am the only volunteer associated)
  When I press the delete event button
  Then I am allowed to delete the event

# Pass
# Theoretically working, as it's blocking if any volunteers attached
Scenario: Host tries to delete event they created, with other some volunteers associated
  Given I am a host
  And I am viewing an event that I created
  And the event has some other volunteers associated
  When I press the delete event button
  Then I am not allowed to delete the event
  And I am shown a message saying 'Sorry, you cannot delete this event while there are volunteers associated.'