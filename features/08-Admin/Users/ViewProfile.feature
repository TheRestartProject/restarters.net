@current
Feature: View profile of an User
    As a User (All roles)
    In to see the profile of the user
    I should be able to see on view profile page.

Scenario: View profile page
    Given I am logged in
    When a user wants to see the biography and skills of a user and click on view profile
    Then they will land on view profile page with their details.

Scenario: Edit User
    Given I am logged in
    When user wants to change the profile, click on edit profile button
    Then user will land on edit profile page.