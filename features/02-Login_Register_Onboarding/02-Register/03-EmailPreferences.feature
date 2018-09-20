Feature: Email Preferences
    As a User
    In order to get notified by the Restart Project
    I should signup for email alerts and save the preferences

 Background:
    Given the user accounts have not been created yet

Scenario: Check Email preferences
    When a user wants to get notified by the Restart Project
    And ticking-off the checkbox and click on next step button
    Then she should land on Data consent page.

Scenario: User wants to go to previous step
    When a user wants to go to previous step, click Previous step link
    Then the user lands on previous page i.e., select skills page