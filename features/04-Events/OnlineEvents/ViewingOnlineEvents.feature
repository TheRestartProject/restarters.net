Feature: Viewing online events
  
Scenario: Viewing single online event
  When I view an online event
  Then I do not see the event map
  And I do not see the event location

Scenario: Viewing online event listings
  When I view a list of online events
  Then I do not see a value in the event location
