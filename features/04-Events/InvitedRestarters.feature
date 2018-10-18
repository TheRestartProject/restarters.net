Feature: View all invited restarters   
   As a User (All roles)   
   In order to view all invited restarters   
   I should be able to click on see all invited link and view the list of restarters.

Background:   
   Given the following account have been created as an host        
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: View all the invited restarters    
   When a user clicks on see all invited link in the events page
   Then a pop up appears with all the list of restarters that have been invited
   And can view the restarter name with their skills.

# Scenario: View all the new users invited
#   When a user clicks on see all invited link in the events page
#  Then a link should say the number of new users invited.