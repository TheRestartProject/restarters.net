Feature: Delete event from WordPress
  
As a group host,
I want event that I delete to also be removed from therestartproject.org,
So that they are no longer publicly visible.

If it isn’t deleted from WordPress successfully, admins should be notified.

Scenario: Event deleted OK
  When a user deletes an event from Restarters.net
  Then it should also be removed from therestartproject.org

Scenario: Event not deleted successfully
  When a user deletes an event from Restarters.net
  And it is not deleted from WordPress successfully at the time
  Then admins should be sent a notification to let them know
