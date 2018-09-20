Feature: Edit Brand Name
    As an admin
    In order to edit brand name
    I should go to edit brand page and click on save brand to save the changes

Scenario: Editing a brand name
    When a brand name is edited, should edit the field as follows and click on save brand button to save the changes
    | Brand name       | 
    | HP               |
    Then she will land on brands name page with the edited brand name in the list, pop-up message saying your changes have beeen saved.