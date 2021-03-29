Feature: View my events listings
    As any user
    In order to keep up to date with events relevant to me
    I want to have access to an events listings page

Scenario: Viewing events page as any user
  Given I am any user
  When I go to the Events page
  Then I see a Your Events section with two tabs: Upcoming and Past
  And an Other Events section with two tabs: Nearby and All

Scenario: Viewing events moderation section as admin or network coordinator
  Given that I am an admin or network coordinator
  When I go to the Events page
  Then I see the Events to Moderate section showing any events to moderate

Scenario: Add events button
  Given that I am a host, admin or network coordinator
  When I go to the Events page
  Then I see a button to add a new event

Scenario: RSVPing to events in the listings
  Given that I am a member who has not RSVPd to an event
  When I go to the Events page, Your Events, Upcoming
  Then I should see an RSVP button and two columns, invited and confirmed

Scenario: RSVPed to an event
  Given that I am a member who has RSVPd to an event
  When I go to the Events page, Your Events, Upcoming
  Then I should see a “You’re going” message and different event styling for the events I have RSVPed to
  
Scenario: Events calendar
  Given that I am any user
  Then I see a button that allows me to subscribe to my events calendar
