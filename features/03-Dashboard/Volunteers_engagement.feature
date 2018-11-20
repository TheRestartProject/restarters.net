Feature: Volunteers engagement on Talk
#Volunteer engagement. Talk is a very important of the platform, where people can get involved and be active even if there are no events or groups currently near them. 
#We want to highlight activity and encourage participation and use of Talk as much as possible.  
   As a user 
   In order to communicate with other volunteers   
   I should be able to navigate to discourse.

Background:   
   Given the following account have been created      
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Navigating to discourse  
   When a user clicks on the hot topic list link on dashboard
   Then he will be taken to talk, hot topics list.

Scenario: User permissions on discourse
   When a user clicks through the discourse link
   Then user will be seen only topics that are in categories they have right to view.
