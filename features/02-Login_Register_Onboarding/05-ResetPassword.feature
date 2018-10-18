Feature: Reset Password
    As a User
    In order to reset my password
    I should be able to do that in password reset page. 

 Given the following user accounts have been created
    | Email                    | Role  |
    | hubert@planetexpress.com | User  |

Scenario: Reset Password
    When a user fills the data as follows
    | Password      | Repeat password  |
    | hubert!       | hubert!          |
    And clicks on change password button
    Then user should land on login page with a message saying the password has been successfully changed.

Scenario: Password Validation
# Password validation rules
    When a user types the password in confirm password field, it should match with password entered before in the password field
    And the password should be equal to or more than six characters
    Then the user will be set up with new password and continue to next process.