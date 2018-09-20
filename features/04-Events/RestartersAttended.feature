Feature: View all the attended restarters    
   As a User (All roles) 
   In order to view all the attended restarters  
   I should be able to click on see all invited link and view the list of attended restarters.

Background:   
   Given the following account have been created as an host      
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: View all the attended restarters    
   When a user clicks on see all attended link in the events page
   Then a pop up appears with all the list of restarters that have attended
   And can view the host of that party
   And can view the restarter name with their skills and also a link to remove the volunteer.