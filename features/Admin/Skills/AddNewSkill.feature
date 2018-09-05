Feature: Add new skill
    As an admin
    In order to add a new skill
    I should fill the fields of add new skill pop-up and click on create new skill button

Background:
    Given the following account have been created as an admin
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: Adding new skill
    When a admin needs new skill to their profile, they should fill the fields as follows
    | Skill name        | Description(optional   | 
    | Mobile devices    |                        |
    | laptops           |                        |
    And click on Create new skill button to save the changes
    Then she should land on all skills page with the new skill added in the list of skills, with a message saying new skill have been added.
