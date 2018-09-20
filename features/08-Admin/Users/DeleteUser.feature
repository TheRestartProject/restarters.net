Feature: Delete User

    As a user
    In order to exercise my right to be forgotten
    I would like to be able to delete my account
    
Scenario: Admin deletes user's account
  Given an Admin is on a user's account page
  When she deletes the users account
  Then the user's personal data is anonymised
  And the account is marked as inactive
  And the Admin is directed to the All Users page
  And the Admin is shown a message showing that this user has been successfully deleted