Feature: Edit devices  
   As a user (all roles)  
   In order to edit the devices  
   I should be able to navigate edit devices page.

Background:   
   Given the following account have been created a restarter       
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Edit devices  
   When a restarter clicks on edit devices page
   And change/update the fields as follows
   | Resatart party | Category of device | Brand  | Model        | Age     | Add devices image here | Repair Status | Repair information | Spare parts required | Description of problem/solution | Suitable for community |     
   | Restart HQ     | Paper shredder     | Amazon | Basics HY245 | 3 years |                        | Repairable    | Do it yourself     | yes                  | fuse blown out                  | tick symbol            |
   And click on save device to save the changes
   Then you will land on all devices page with the edited device on the list of devices.

Scenario: delete device  
   When a restarter wants to delete a device, click on delete device button
   Then you will land on all devices page and you won't be able to see the deleted device from the list of devices.

