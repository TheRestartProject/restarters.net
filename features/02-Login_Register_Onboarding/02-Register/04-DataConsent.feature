Feature: Data Consent
    As a User
    In order to know how my data is to be used 
    I should give my acceptance to Restartproject 

 Background:
    Given the user accounts have not been created yet

Scenario: Check preferences
    When a user gives acceptance to his/her data to be used by the Restartproject
    And ticking-off the checkbox and click on Complete my profile button
    Then user should land on dashboard page with pop up of onboarding process.

Scenario: User wants to go to previous step
    When a user wants to go to previous step, click Previous step link
    Then the user lands on previous page i.e., select skills page