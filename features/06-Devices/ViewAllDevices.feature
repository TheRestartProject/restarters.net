Feature: View all devices  
   As a user (admins)  
   In order to see all the devices that was taken to restart party   
   I should be able to navigate devices page.

Background:   
   Given the following account have been created a restarter       
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: View all devices  
   When an admin clicks on devices page
   Then he can see all the devices starting from recent ones on the top of the page.

Scenario: search for a particular device  
#Users can search for devices by either taxonomy or by date or by other various ways like device Id etc. 
   When an admin wants to search for the devices, he can fill the fields as he want to search as follows
   | Category      | Group         | From date   | To date     | Device ID     | Device brand | Device model | Search comments |       
   | flat screen   | Restart HQ    | 23/04/2017  | 23/04/2017  |               |              |              |                 |
   And should click on search all devices button
   And can also edit, delete the devices
   Then user can view the list or a particular device that searched for.

Scenario: View all devices and filter them by the hosts and restarters also
#example: Repairer is at an event trying to fix an Apple iPhone 5s. They should be able to easily log in to restarters.net on their mobile/tablet, search for brand Apple, model iPhone, and see past repair problems and solutions and sources of repair
   When a host/restarter clicks on devices page
   Then he can only view the devices starting from recent ones on the top of the page
   And can search for devices filtering by brand, model
   And can see past repair problems and solutions/sources of repair
   Then they should be able to see the filtered results.

Scenario: Hosts wants to view how many devices they have fixed at their group's event
   When a host filters by repair status
   And also can order (either ascending or descending) devices by each column by clicking on headers
   Then he can only view the result devices
   And cannot export them.
