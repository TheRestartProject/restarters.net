Feature: Automatic login to Discourse
  
Scenario: logging in to Restarters logs in to Discourse
  Given I login with a valid user
  # Navigate to https://restarters.dev
  # Enter valid login details
  When I navigate to Talk
  # Navigate to https://talk.restarters.dev via the global nav
  Then I can see that I am already logged in to Talk
  # Check for presence of user menus to indicate logged in
