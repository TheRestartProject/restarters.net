Feature: Edit skill
    As an admin
    In order to add edit my skill
    I should navigate to edit skill page and click on save skill to save the changes

Background:
    Given the following account have been created as an admin/user
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: Editing a skill
    When an admin edit a skill which is in their profile, they should edit the fields as follows
    | Skill name        | Description(optional   | 
    | Scanners          |                        |
    | laptops           |                        |
    And click on save skill button to save the changes
    Then she will land on all skills page with the edited skill in the list of skills, with a message saying your changes have been saved.

Scenario: Deleting a skill
    When an admin wants to delete a skill which is in their profile
    And click on delete skill button to delete the skill
    Then she will land on all skills page where the deleted skill will no longer be there in the list of skills, with a message saying your skill have been deleted.
