Feature: Automatic login to the Wiki
  
When a user joins Restarters they are created an account on the Wiki.
The Wiki runs on MediaWiki.
When they log in to Restarters, they are automatically logged in to MediaWiki.
  
Scenario: Logging in logs in to Wiki
  Given I log in with a valid user
  # Navigate to https://restarters.dev
  # Enter login details
  When I navigate to the Wiki
  # Navigate to https://wiki.restarters.dev via the global nav
  Then I can see that I am already logged in to the Wiki
  # Check for presence of user menus
