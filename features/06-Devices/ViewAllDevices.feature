Feature: View all devices  
   As a user (all roles)  
   In order to see all the devices that was taken to restart party   
   I should be able to navigate devices page.

Background:   
   Given the following account have been created a restarter       
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: View all devices  
   When a restarter clicks on devices page
   Then he can see all the devices starting from recent ones on the top of the page.

Scenario: search for a particular device  
#Users can search for devices by either taxonomy or by date or by other various ways like device Id etc. 
   When a restarter wants to search for the devices, he can fill the fields as he want to search as follows
   | Category      | Group         | From date   | To date     | Device ID     | Device brand | Device model | Search comments |       
   | flat screen   | Restart HQ    | 23/04/2017  | 23/04/2017  |               |              |              |                 |
   And should click on search all devices button
   Then user can view the list or a particular device that searched for.

Scenario: Export filtered devices
   Given user wants to get the data of filtered devices 
   When a user clicks on edit devices page
   And enters the data needed to filter 
   And click on export file button
   Then the user will get the data of the filtered list of devices only.