Feature: Invite emails that are sent to people who are not on platform
   As a user (all roles)  
   In order to invite people to the platform   
   I should be able to send emails with details of the platform along with sign up button.

Background:   
   Given the following account have been created an admin     
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     |

Scenario: Inviting new people to the platform
   When the admin invites new users, enters the email id of the people and clicks on send button
   Then the new people will get the email containing information about the platform, benefits and why would someone want to sign up along with sign up button.