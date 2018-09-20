Feature: Total hours volunteered  
   As a user (all roles)  
   In order to see the total time volunteered    
   I should be able to navigate to time volunteered page.

Background:   
   Given the following account have been created a restarter       
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Total time volunteered 
   When a restarter wants to see the total time volunteered 
   Then he can see all the information about volunteered time on time volunteered page.

Scenario: search for a paticular period of time volunteered 
#Users can search for devices by either taxonomy or by date or by users or by location and miscellaneous. 
   When a restarter wants to search for a particular period of time volunteered, he can fill the fields as he want to search as follows
   | Group      | Group tag    | Name   | Age range | Gender | From date  | To date    | Country | Region | Include anonymous users |     
   | Restart HQ | Exampletag1  | James  | 23-28     | Male   | 23/04/2017 | 12/08/2017 | UK      | London | Yes                     |
   And should click on search all time volunteered
   Then user can view the list of time volunteered.