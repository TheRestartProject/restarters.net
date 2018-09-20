Feature: Forgot Password
    As a User
    In order to get a new password
    I should be able to do that in forgot password page. 

 Given the following user accounts have been created
    | Email                    | Role  |
    | hubert@planetexpress.com | User  |

Scenario: Forgot Password
    When a user completes the fields as follows
    | Email address              |
    | hubert@planetexpress.com   |
    And clicks on reset button
    Then user should land on same page with a message saying the please check your email and follow.

Scenario: Invalid email ID
    When a user enters wrong email id or the email id is not present in database
    And clicks reset button
    Then the user lands on same page with an error.

Scenario: I remembered Password 
    When a user remembers the password
    And clicks on the link I remembered. Let me sign in
    Then the user lands on login page.

Scenario: User triggers password reset request email
   When the user clicks the forgot password link
   Then the user would receive an email to his registered email account, to reset password.
