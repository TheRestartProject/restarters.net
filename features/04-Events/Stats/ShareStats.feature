Feature: Share your Stats 
   As a User (all roles)  
   In order to share the stats of a particular event to other place   
   I should be able to click on Events stats embed button on events pages.

Background:   
   Given the following account have been created as an host        
   | Email                      | Password |        
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Sharing your stats 
   When a user wants to share their stats to other places, click on Events stats embed button
   And a share your stats from this event pop up screen is displayed along with an infogrpahic 
   And copy the links required and use them 
   And click on cancel symbol 
   Then the user will be back on events page.