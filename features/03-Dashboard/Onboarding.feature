Feature: Onboarding steps
    As a User
    In order to know how the platform works 
    I should see onboarding process

 Background:
    Given the following account have been created as a user
        | Email                      | Password |
        | jenny@google.co.uk         | dean1    | 

Scenario: Onboarding process
    When a user sees the onboarding process
    And click on next button or previous button
    Then user sees next or previous part of onboarding process.

Scenario: Clicking on Create new party
    When a user wants to create a party after going through the onboarding process
    And clicks on create new party button
    Then the user lands on party creation page.

Scenario: Clicking on cancel
    When a user wants to go to dashboard after going through the onbosrding process
    And clicks cancel symbol X
    Then the user lands on dashboard page.