Feature: Search - All users
    As an admin
    In order to see all users or search for a particular user
    I should be able to do by using a search button

Background:
    Given the following account have been created as an admin
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

# Valid search - Multiple scenario's
Scenario: Entering details in all the fields
# Entering correct details in the fields provided at By details category.
    When an admin enter details of a particular user in the feilds provided as follows
    | Name       | Email                  | Town/City   | Group      | Role       | 
    | jenny      | jenny@gmail.com        | London      | Remakery   | Host       |
    | diamond    | diamond@gmail.com      | Belgium     | Group2     | Volunteer  |
    | james      | james@yahoo.com        | Portsmouth  | Group3     | Restarter  |
    Then the admin should get the details of that particalr user.
    
Scenario: Entering details in only one field like name or email
# Entering correct details in the fields provided at By details category.
    When an admin enter details of a particular user in the feilds provided as follows
    | Name       | Email                  | Town/City   | Group      | Role       | 
    | jenny      |                        |             |            |            |
    |            | diamond@gmail.com      |             |            |            |
    Then the admin should get the details of that particalr user.
    
Scenario: Entering details in any two of the fields like name and Town/City or Email and Role or name and group 
# Entering correct details in the fields provided at By details category.
    When an admin enter details of a particular user in the feilds provided as follows
    | Name       | Email                  | Town/City   | Group      | Role       | 
    | jenny      |                        | London      |            |            |
    |            | diamond@gmail.com      |             |            | Volunteer  |
    | james      |                        |             | Group3     |            |
    Then the admin should get the details of that particalr user.

Scenario: Invalid Search
# Not entering any of the fields and clicking on search all users button.
    When an admin does not enter any field as follows
    | Name       | Email                  | Town/City   | Group      | Role       | 
    |            |                        |             |            |            |
    And clicks on search users button
    Then she will land on All users page without any changes.