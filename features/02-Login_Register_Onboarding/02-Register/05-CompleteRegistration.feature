Feature: Complete Registration
    As a User
    In order to use the community platform and view all the events and other platforms
    I should register myself onto the community platform and the system should create an account when I register.

Background:
    Given the following account have been created as a user
        | Email                      | Password |
        | jenny@google.co.uk         | dean1    | 

Scenario: System creating an account when I register
    When a user gets registere themselves on the community platform
    Then an account should be created within the system.

Scenario: Creating accounts on Wiki and Discourse.
    When a user creats an account onto  the system 
    Then the user would automatically creates an account on Wiki and Discourse with same details
    And directly login in wiki and discourse.