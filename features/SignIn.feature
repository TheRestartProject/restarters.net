Feature: User Authentication
    As a user
    In order to perform what I want to do on the site
    I want to be able to log in

Background:
    Given a user account with has been created with email "fry@planetexpress.com" and password "fry!"

    #Given the following user accounts have been created
    #    | Email                 | Password |
    #    | fry@planetexpress.com | fry!     | 

Scenario: Valid login
    When a user logs in with email "fry@planetexpress.com" and password "fry!"
    Then the user is logged in as "Fry" with email "fry@planetexpress.com"

Scenario: Valid login with alternate case email
  Emails are case-insensitive.
     When a user logs in with email "FRY@PlAnetExPreSs.com" and password "fry!"
     Then the user is logged in as "Fry" with email "fry@planetexpress.com"

Scenario: Invalid login due to password casing
# Passwords are case-sensitive.
     When a user logs in with email "fry@planetexpress.com" and password "aaaaFRY!"
     Then the user is not logged in
     And "These credentials do not match our records." message is displayed to the user letting them know they have not been logged in