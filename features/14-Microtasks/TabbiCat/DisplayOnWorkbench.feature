Feature: Show on Workbench dashboard
  
In order to let the community know that TabbiCat is active
As Restart
We want to feature the TabbiCat quest on the Workbench dashboard

Scenario: TabbiCat featured on Workbench
  Given I am any user, logged in or logged out
  And we have passed the launch date and time of TabbiCat
  When I visit the Workbench page
  # https://restarters.dev/workbench
  Then I see TabbiCat described in the CTA section 
  # exact text to be decided
  And when I click ‘Get involved’ I am taken to the TabbiCat quest
  # https://restarters.dev/workbench/tabbicat
