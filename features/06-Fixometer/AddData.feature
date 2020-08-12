Feature: Add data quickly from the Fixometer
  
In order to easily the amount of data collected,
As an admin or host, 
I want to quickly add data from the Fixometer section.

Scenario: someone who should be able to add data
  Given I am a host or admin, or a repairer who has been to an event
  When I visit the Fixometer page
  Then I see the Add Data button

Scenario: someone who shouldn't be able to add data
  Given I'm a repairer who has never been to an event
  When I visit the Fixometer page
  Then I don't see the Add Data button
