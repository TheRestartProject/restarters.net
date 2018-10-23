Feature: Amend number of volunteers
  
In order to keep records of estimated number of volunteers
As an Admin or a group host
I need to be able to amend the number of volunteers that attended an event.

We need a way to manually amend the number of volunteers that attended an event.
Ideally the number would automatically set to the right amount by invites/RSVPs,
but this feature is not being widely enough used yet.

It's only possible to manually amend the number of volunteers for an event after the event
has started

Scenario: Can't amend volunteers until event has started
# Pass

Scenario: Admin amends number of volunteers for event
  Given I am an Admin
  When I change the number of volunteers for an event
  Then the amended value is saved
# Pass

Scenario: Host of a group amends number of volunteers for event
  Given I am a host of a group
  When I change the number of volunteers for an event from my group
  Then the amended value is saved
# Pass

Scenario: Host of another group should not be able to amend the number of volunteers for event from other group
  Given I am a host
  When I visit the event page of an event from another group
  Then I do not have the option to amend the number of volunteers
# Pass
  
Scenario: Manually amended number of volunteers differs from number of volunteers linked
  Given I am an Admin or a Host
  When I change the number of volunteers
  And the new figure is different from the number of volunteers associated with the event
  Then I should see a message saying 'Please note that the number of volunteers does not match the attendance record.  Do you need to add or remove volunteers?'
# Fail
# Message is incorrect
  

# General Fail
# Somewhat unrelated, but I get the message 'Something went wrong' when editing number of participants