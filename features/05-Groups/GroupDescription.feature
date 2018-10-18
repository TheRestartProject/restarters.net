Feature: View a Group description
    As a user (all roles)
    In order to view a particular group description
    I should be able to click on read more link on that particular group page

Background:
    Given the following account have been created as a restarter
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: To view description about group
    When a restarter wants to know about a group and clicks on read more link
    Then a pop screen appears with full description of the group.

 Scenario: Cancel the pop up screen
    When a restarter wants to close the pop up screen and go back to that group page
    Then he can click on cancel, will land on group page.
