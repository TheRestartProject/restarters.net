Feature: Add New User
    As an Admin
    In order to create new user accounts for cases when the user can't self-register
    I would like a Create New User facility.
    
Scenario: Creating new users
    Given an Admin user is on the All Users page
    When she clicks the New User button
    Then she is shown the dialog for creating the new user

Scenario: Valid details added for user
# Entering correct details in the fields provided at Add new user pop-up.
    Given an Admin is creating a new user
    When she enters the new user's details in the fields provided as follows:
    | Name       | Email address          | User role   | Password   | Repeat password | 
    | diamond    | diamond@gmail.com      | Volunteer   | h£!!05     | h£!!05          |
    | james      | james@yahoo.com        | Restarter   | scr7vd*    | scr7vd*         |
    And she clicks 'Create new user'
    Then she lands on the All Users page with the newly added user in the list of users
    And she is shown a message saying that new user has been added successfully

Scenario: Invalid details added for user
# Entering invalid details in the fields provided at Add new user pop-up.
    Given an Admin is creating a new user
    When she enters the new user's details in the fields provided as follows:
    | Name       | Email address          | User role   | Password   | Repeat password | 
    | diamond    | diamond@gmail.com      | Volunteer   | h£!!       | h£!!            |
    And she clicks 'Create new user'
    Then an error message should at the password field, password should be more than 6 characters.