Feature: View a Group
    As a restarter
    In order to view a particular group details
    I should be able to go to groups page and click on a particular group link

Background:
    Given the following account have been created as a restarter
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: To view information about the group
    When a restarter wants to know the information about a group
    Then he can view on the particular group page
    And can find all the info like group address, website, key stats, device breakdown, environmental impact, upcoming events and recently completed events.

Scenario: To view description about group
    When a restarter wants to know about a group
    Then he can view under about the group section
    And can even click on read more for more info about the group.

 Scenario: View Volunteers in group
    When a restarter wants to know the volunteers who are present in that group
    Then he can view under volunteers section.

Scenario: Join as a Volunteer in that group
    When a restarter wants to join as a volunteer in that group
    Then he can click on join gropu link under volunteers section.

Scenario: View upcoming events
    When a restarter wants to attend an event
    Then he can click on RSVP link
    And can add a device to an event which is happening by clicking on add a device link.
    
Scenario: See all events
    When a restarter wants to see all the events that completed recently
    Then he can click on see all events links
    And can add a device by clicking on its link.

Scenario: Restarter triggers notification email to host by joining the group
   When the restarter clicks on join group button
   Then the host would receive an notification email about that restarter joining the group.