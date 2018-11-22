Feature: Invite volunteers to a group by email
   As a User (All roles)   
   In order to invite volunteers to a group    
   I should be able to click on invite to group link.

Background:   
   Given the following account have been created as an host        
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Invite volunteers to a group  
   When a user clicks on Invite to group from the group page
   Then a pop up appears, where email address and message should be entered as follows
   | Email address              | Message      |       
   | dean@wecreatedigital.co.uk | Hi, Welcome! | 
   And can click on send invite button.

Scenario: User triggers invitation to group email
   When the user clicks the send invite to group button
   Then the volunteer that the user has sent invite to group would receive an email about information of that group.