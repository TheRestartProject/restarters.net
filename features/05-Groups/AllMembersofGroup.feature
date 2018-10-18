Feature: View all volunteers of a group 
   As a User (All roles)   
   In order to view all volunteers of a group    
   I should be able to click on Join group link.

Background:   
   Given the following account have been created as an host        
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: View all the volunteers of a group  
   When a user clicks on Join group from the group page
   Then a pop up appears with all the list of restarters with their skills
   And can click on join group button.