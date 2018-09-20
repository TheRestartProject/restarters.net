#Feature: Upcoming Parties

#A list of upcoming parties.

#They should display until the end time of the party.

#How many should be listed - all future ones?
#-------------------------------------------------------------------------------------------------------#-----------

Feature: Upcoming Parties
    As an Admin
    In order to view the upcoming parties on the wordpress site(main website - https://therestartproject.org/)
    I should be able to do it through API call.

Scenario: View created Upcoming events
    When an admin approves an event
    Then they would see the approved event in the list of events in upcoming events section on the wordpress site
    And an event page is created on the wordpress site
    And the event would appear till the end of the event date and time.

Scenario: View edited upcoming events
    When an admin/host edits an approved event
    Then they would see the edited event in the list of events in upcoming events section on the wordpress site
    And the changes made would appear on the event page created in wordpress site
    And the event would appear till the end of the event date and time.

Scenario: Delete upcoming events
    When an admin/host deletes an approved event
    Then they will not see the deleted event in the list of events in upcoming events section on the wordpress site
    And the event page created in wordpress site will also be deleted.