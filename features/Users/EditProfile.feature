Feature: Edit profile

As a user of Restarters.net
I want to be able to edit my profile
So that I can keep my details up to date

Scenario: Editing email
  Given I have an account on Discourse
  When I update my email address in my profile
  Then the email address update is also synced to Discourse
