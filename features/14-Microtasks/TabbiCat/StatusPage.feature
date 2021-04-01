Feature: TabbiCat status page
  

Scenario: View status page
  Given I am any user, logged in or out
  When I visit the TabbiCat status page
  # https://restarters.dev/workbench/tabbicat/status
  Then I am presented with tables of app status information and statistics
  # TODO: explain further on what we should see...

Scenario: Navigate to task page
  Given I am any user, logged in or out
  And I am on the status page
  When I click the TabbiCat icon (TBD)
  Then I am returned to the TabbiCat task page
