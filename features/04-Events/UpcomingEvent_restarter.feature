Feature: Upcoming events details  
   As a restarter   
   In order to view the upcoming events    
   I should be able to navigate to the upcoming event page.

Background:    
   Given the following account have been created as a restarter     
   | Email                      | Password |        
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: View upcoming events details 
   When a restarter wants to view the upcoming event details- event address, description, attendance
   Then he can see on the upcoming event page.

Scenario: Add to calendar  
   When a restarter wants to attend the party and wants add to calendar
   Then click on add to calendar button
   And the event will be added to your calendar.

Scenario: Volunteer triggers RSVP button and sends email to host
   When the volunteer clicks the RSVP button
   Then the host would receive an email about status of the volunteer.