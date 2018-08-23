Feature: Send Invite's to restarters    
   As a User (All roles)  
   In order to send invite's to restarters   
   I should be able to click on invite button on events pages.

Background:    
   Given the following account have been created as an host        
   | Email                      | Password |        
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Inviting restarters to the event 
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
   When a user gives invalid email id 
   And clicks on send invite button
   Then an error message will display.

Scenario: User triggers invitation to an event email
   When the user clicks the send invite button
   Then the volunteer(s) that the user has sent sent invite to an event would receive an email about information on that event.