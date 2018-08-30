Feature: Filter Events
    As a User (admin, host)
    In order to filter events
    I should be able to do so by navigating to events filter page

Background:
    Given the following account have been created as an admin
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: To search for an event
    When an admin wants to search for an event, fill the fields as follows
    | By group          | By event          | From date   | To date     | Group tag    |
    | Mighty Restarter  | history event     | 23/04/2017  | 09/11/2018  | Exampletag1  |
    | Mighty Restarter  |                   | 23/04/2017  | 09/11/2018  |              |
    And its not mandatory to fill all the details, they are optional
    And click on filter results button
    Then he can view the filtered event results year wise in descending order along with other information.