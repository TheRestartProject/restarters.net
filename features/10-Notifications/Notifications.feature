Feature: View Notifications
    As a User (All roles)
    In order to view all the notifications
    I should be able to click on notification symbol with viewing notifications in it.

Background:
    Given the following account have been created as an host
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: View all notifications
# View all events i.e., notifications related to events, devices and groups
    When a host clicks on notification icon 
    Then a side view appears with all the notifications in it.
    
Scenario: Identifying new notifications
    When a host wants to know if he got a notification or not, just simply by looking at the number near the symbol tells how many notifications are present
    Then if any notification is present then host will open else not.
    
Scenario: View upcoming events
    Given there is a upcoming event
    When user views notifications
    Then they should see notifications of upcoming events.
    
Scenario: Clicking links inside notifications
    Given user has notifications of upcoming events
    When they click on the link in that notification
    Then they land to that upcoming event page.

Scenario: No notifications
    Given there are no notifications
    When a host clikcs on notification symbol, even though they did not get any notification(for the first time)
    Then there will be a welcome message.

Scenario: Mark as read
    Given there are notifications
    When a host wants to mark the notification as read
    And click on mark as read link
    Then the user will be able to see the text changed to marked as read
    And als othe background color changes.

Scenario: View old notifications
    Given there are old notifications
    When a host clikcs on view old notifications link
    Then users will see the list of older notifications.    