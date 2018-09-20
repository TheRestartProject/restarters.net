Feature: Record Volunteer   
   As a User (host, admin)  
   In order to record the volunteer who directly came to the event    
   I should be able to click on add volunteer button on events pages.
   
Background:    
   Given the following account have been created as an host        
   | Email                      | Password |        
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Recording volunteer's who came to the event directly(no RSVP) 
   When a user clicks on add volunteer button, a pop up screen of add volunteer is displayed 
   And fill in the fields as follows
   | Group member       |Full name  | Volunteers email address(optional) |     
   | Hackney fixers     | Dean      | d@wcd.co.uk                        | 
   | Remakery           | John      | j@dcw.co.uk                        | 
   And click on volunteer attended button
   Then host will land on event page with the added volunteer in the list of volunteers attended with a message saying the volunteer has bee successfully recorded.

Scenario: Invalid email address 
   When a user gives invalid email id 
   And clicks on send invite button
   Then an error message will display.

Scenario: Invalid Group name
   When a user gives invalid group name
   And clicks on send invite button
   Then an error message will display.