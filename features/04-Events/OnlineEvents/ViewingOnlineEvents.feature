Feature: Viewing online events
  
Scenario: Viewing online event
  When I view an online event
  Then I do not see the event map
  And I do not see the event location
