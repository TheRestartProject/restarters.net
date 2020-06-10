Feature: Restarter becoming a host
    As a restarter
    In order to become a host
    I should be able to create a new group by clicking on create new group button on group page

Background:
    Given the following account have been created as a restarter
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: To create a new group
    When a restarter clicks on create new group
    Then a pop up appears with message and a button to get started.
    
Scenario: Cancel creating a group
    When a restarter does not want to create a group and wants to go back to all groups page
    Then he should click on cancel to go back.