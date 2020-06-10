Feature: View a Group
    As a host
    In order to view a particular group details
    I should be able to go to groups page and click on a particular group link

Background:
    Given the following account have been created as a host
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: To view information about the group
    When a host wants to know the information about a group
    Then he can view on the particular group page
    And can find all the info like group address, website, key stats, device breakdown, environmental impact, upcoming events and recently completed events.

Scenario: To view description about group
    When a host wants to know about a group
    Then he can view under about the group section
    And can even click on read more for more info about the group.

 Scenario: View Volunteers in group
    When a host wants to know the volunteers who are present in that group
    Then he can view under volunteers section.

Scenario: Add Volunteers in group
    When a host wants to add the volunteers in that group
    Then he can click invite to group link under volunteers section.

Scenario: Add event
    When a host wants to add an event
    Then he can click on add event link
    And can RSVP and can also add a device by clicking on respective links.

Scenario: See all events
    When a host wants to see all the events that completed recently
    Then he can click on see all events links
    And can add a device by clicking on its link.
