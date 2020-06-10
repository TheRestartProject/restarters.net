Feature: Edit a group
    As a User (Host, Admin)
    In order to edit a group
    I should be able to do by navigating to edit group page

Background:
    Given the following account have been created as a host or an admin
        | Email                      | Password | Role  |
        | dean@wecreatedigital.co.uk | dean     | Host  |
        | hello@howareyou.com        | hello    | Admin |

Scenario: Editing a group
    When a host clicks on edit group page and edits the data as follows
    | Name of group     | Your website         | Tell us about your group  | Group location | Group image  | Group tags|
    | Mighty Restarter  | https://mire.co.uk   | experts in fixing things  | Southwark      | :)           | Exampletag1           |
    And clicks on approve group button to save the changes
    Then he lands on group page with the edited group in the list of gropus in that page.
    
Scenario: Text cleaned in the description
    When a host copies and paste into the description box
    And the data should loose all htmls and css properties it has
    Then it show a message inside description box as text cleaned.

Scenario: How to give group location 
    When a host clicks on group location, types the address
    Then automatically suggestions should show up and the place should be pointed in map.

Scenario: searching the image
#TODO: when clicked on add group image here text, file explorer opens.
    When user clicks on add image text, then file explorer should open
    And browse for the image
    And select the one needed
    Then you will see the uploaded image thumbnail in that area.

Scenario: Adding group tags
#Only admin can add a new tag 
    When an admin clicks on add new tag link beside group tags and edits the data as follows
    | Group tags|
    | Exampletags1          |
    Then the edited tag appers in the field with cancel option, if needed we can delete the tag using cancel option.