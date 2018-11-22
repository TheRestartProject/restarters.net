Feature: Sharing social media friendly Stats 
   As a User (all roles)  
   In order to share the stats of a particular event or to social media platforms   
   I should be able to click on social media friendly stats button.

Background:   
   Given the following account have been created as an host        
   | Email                      | Password |        
   | dean@wecreatedigital.co.uk | dean     | 

#when a group or host doesn't have a webiste, cannot share their stats. So, in order to share their stats social media friendly images of their stats are being created.

Scenario: Sharing social media friendly images for their events impact stats
   Given the user is a host or volunteere of the event
   When a user clicks on the the share social media friendly stats button on the event page
   Then they can see the images on a popup screen
   And can share them.

Scenario: Sharing social media friendly images for their group impact stats
   Given the user is a member of the group
   When a user clicks on the the share social media friendly stats button on the group page
   Then they can see the images on a popup screen
   And can share them.
