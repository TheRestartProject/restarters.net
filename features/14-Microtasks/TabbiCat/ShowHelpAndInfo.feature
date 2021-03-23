Feature: Show TabbiCat Help

Scenario: Navigate to info modal
  Given I am any user, logged in or out
  When I click on the <i icon> from either a task or the status page
  Then I am presented with a modal containing help and further information about TabbiCat.




