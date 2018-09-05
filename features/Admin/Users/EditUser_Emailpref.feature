Feature: Email Preferences
    As an Admin
    In order to get notified by the Restart Project
    I should signup for email alerts and save the preferences

Background:
    Given the following account have been created as a user
        | Email                      | Password |
        | jenny@google.co.uk         | dean1    | 

Scenario: Check Email preferences
    When an admin wants to get notified by the Restart Project
    And ticking-off the checkbox and click on save preferences button
    Then she should land on Email preferences page with a message saying that the changes have been saved.

Scenario: Creating an email.
# User can create a email or set an email to Restart Project discussion platform and click on save preferences button
    When a user create a email or set an email to Restart Project discussion platform
    Then the user receives the information to that email id