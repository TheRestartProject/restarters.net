Feature: Prompt user to join

In order to get more data volunteers,
As Restart,
We want to encourage anonymous TabbiCat users to join Restarters

Scenario: Anonymous user does 5 tasks
  Given I am an anonymous user
  When I have done 5 tasks
  Then I should be shown a modal showing me why joining is a good idea
  And with the buttons 'Sign Up' and 'Not now'
  
Scenario: Anonymous user chooses to sign up
  Given I am an anonymous user
  And I am being shown the 'Join' CTA
  When I click/touch the 'Sign Up' button
  Then I am taken to the registration section of Restarters

Scenario: Anonymous user doesn't want to sign up
  Given I am an anonymous user
  And I am being shown the 'Join' CTA
  When I click/touch the 'Not now' button
  Then I am taken back to the TabbiCat quest
  And I am not prompted to join again for another 20 minutes

  
Scenario: Logged in user not promtped
  Given I am a logged in user
  Then I am never shown the prompt to join while I am logged in
