Feature: Share your Stats 
   As a User (all roles)  
   In order to share the stats of a particular event to other place   
   I should be able to click on Events stats embed button on group page.

Background:   
   Given the following account have been created as an host        
   | Email                      | Password |        
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Sharing your stats 
   When a user wants to share their group stats to other places, click on Group stats embed button
   And a pop up appreas with iframe as headline stats and CO2 equivalence visualisation
   And copy the links required and use them 
   And click on cancel symbol
   And preview widget link is useful for how the iframe looks visulally on screen 
   Then the user will be back on group page.