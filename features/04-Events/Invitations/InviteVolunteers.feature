Feature: Send event invites to volunteers    
   As a host
   In order to help boost volunteer attendance at events 
   I should be able to invite volunteers to events
   
Hosts can send invitations to volunteers inviting them to come to their event.

Background:    
   Given the following accounts:
   | Email                 | Password |
   | fry@planetexpress.com | fry!     |

Scenario: Inviting volunteers to an event 
   When a user clicks on invite button, invite restarters a pop up screen is displayed
   And user can check the checkbox so that all the restarters associated in that group will get the invite or host can send invites manually by entering the email address of the restarter as follows
   | Email address              |     
   | d@wcd.co.uk                | 
   And also can send an invitation message in the textarea provided as follows
   | Invitation message            |     
   | Hi, Hope to see at the event! | 
   And click on send invite button
   Then host will land on event page with number of invites in the attendace section also a message saying the invites have been sent successfully.

Scenario: Invalid email address 
   When a user gives invalid email address
   And clicks on send invite button
   Then an error message will display

