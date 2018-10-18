Feature: Add new group tag
    As an admin
    In order to add a new group tag
    I should fill the fields of add new group tag pop-up and click on create new tag button

Scenario: Adding new group tag
    When the fields are added as follows
    | Tag name          | Description(optional   | 
    | Example tag1      |                        |
    | Example tag2      |                        |
    And should click on Create new tag button to save the changes
    Then she should land on group tag page with the recently added group tag in list of tags.