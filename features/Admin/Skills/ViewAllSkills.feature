Feature: All Skills
    As a Admin
    In order to know the skills
    I should be able to see them with description

Background:
    Given the following account have been created as a admin
        | Email                      | Password |
        | jenny@google.co.uk         | dean1    |
        | dean@google.co.uk          | helo1    | 

Scenario: View All skills with description
    When an admin wants to know the description of skills
    Then they can navigate to the Skills page.

Scenario: Create new skill button
    When an admin wants to add a skill to their profile
    Then click on create new skill button and follow the steps.
