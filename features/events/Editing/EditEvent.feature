Feature: Edit an event
    As a User (Host, Admin)
    In order to edit a new event
    I should be able to do by navigating to edit event page

Background:
    Given the following account have been created as a host or an admin
        | Email                      | Password | Role  |
        | dean@wecreatedigital.co.uk | dean     | Host  |
        | hello@howareyou.com        | hello    | Admin |

Scenario: Editing a event
    When a host clicks on edit event page and changes/updates the data as follows
    | Name of event     | Event group | Description                | Date of event | Start/end time | Venue address  | Add event image  |
    | Ram               | vanarulu    | group in fixing things     | 7/6/2018      | 20-24          | Remakery       | Add event image  |
    And clicks on save party button
    Then host lands on all events page with the edited event in the list of events.
    
Scenario: Text cleaned in the description
    When a host copies and paste into the description box
    And the data should loose all htmls and css properties it has
    Then it show a message inside description box as text cleaned.
    
Scenario: Calender pop-up on Date of event
    When a host clicks on date field, calendar should pop up 
    And select a date when to arrange party
    Then host lands on the same page and continues with next process.
    
Scenario: When clicked on start time, automatically generate 3hr+ as end time
    When a host clicks on start time, automatically from then +3hr time is calculated as follows
    | Start/end time  |
    | 14:00     17:00 |
    Then that time is stored in the end time field.

Scenario: How to find Venue adddress 
    When a host clicks on venue address, types the address
    Then automatically suggestions should show up and the place should be pointed in map.

Scenario: searching the image
#TODO: when clicked on add group image here text, file explorer opens.
    When user clicks on add image text, then file explorer should open
    And browse for the image
    And select the one needed
    Then you will see the uploaded image thumbnail in that area.

Scenario: Admin triggers view event email
   When the admin clicks the approve event button
   Then the host would receive an email about confirmation of that event.