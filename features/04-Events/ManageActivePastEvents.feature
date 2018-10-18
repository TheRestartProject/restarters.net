Feature: Manage active/past events   
   As a User (host or admin)   
   In order to manage the events   
   I should be able to navigate to manage an event page.

Background:   
   Given the following account have been created as an host       
   | Email                      | Password |        
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Manage active/past events
#  Manage active/past events i.e., past evetns, upcoming events and moderate events    
   When a host clicks on particular event page
   And likes to either edit or update any changes or see environmental impact, attendees, event details etc.,
   Then he can see on that particular event page.
 
Scenario: Deleting or adding a volunteer  
   When a host wants to login the absence of volunteers who RSVPed and presence of volunteers who came directly to the event
   And host wants to manage that    
   Then he can manage it in the attendace section of the event page 
   And can delete or add a volunteer through the links provided.

Scenario: Inviting volunteers to the event    
   When a host wants to invite volunteers to the event, can send invite via emails 
   And he can do this in the attendance section in invites tab   
   Then host can see the number of invites sent to the volunteers in that tab.    

Scenario: Add/ Manage a device details    
   When a host has entered the devices that hase been fixed, repairable and end of life
   And can see the list in the devices section
   And host wants to either add/update a device then click on add button for a new device
   And click on edit link of particular device to be updated and fill the details as  follows 
   | Status | Repair info | Spare parts | Category       | Brand   | Model | Age    | Description of problem/solution | Add image |     
   | Fixed  | More time   | Yes         | Flat screen TV | Toshiba | 123   | 3 years| Doesn't require memory card     |           |
   Then click on save button
   And we can find the new/ updated device in the list of devices.

Scenario: Automatic Post event device upload reminder email
   When 24hours has passed since an event has finished
   Then the post event device upload reminder email shouldbe sent to the host of the event.

Scenario: Host triggers post event device edit reminder email
   When the host clicks the send email to restarters button
   Then all the restarters that attended the event would receive an email to reminder them to edit device information.

Scenario: Host/restarter triggers email by marking description of a repair suitable to wiki
   When the host/restarter marks the description of a repair suitable to wiki and clicks save
   Then admin would receive an email to view the repair notes.