Feature: Invitation notifications
   As a volunteer
   In order to keep track of the events I am attending
   I want to be notified when I have been invited to volunteer at an event
   
Volunteers are notified when they have been invited to an event.  They will receive an in-app notification,
and, if they have opted-in to email notifications, they will also receive an email notification.
  
The email should look as below (right-click and view to see full size):

<img src="invitation-email-not-on-platform.jpg" style="height:500px"/>

Background:
  Given the following users:
| Name  | Role      | Receive invites? |
| Leila | Host      | Yes              |
| Fry   | Restarter | No               |

Scenario: Invitation to volunteer already on platform, opted-in to emails
   When Leila is invited to an event
   Then Leila receives a in-app notification letting them know that they have been invited
   And Leila receives an email notification

Scenario: Invitation to volunteer already on platform, opted-out of emails
   When Fry is invited to an event 
   Then Fry receives an in-app notification letting them know that they have been invited

Scenario: Invitation to volunteer not already on platform
   When a new volunteer, without an account on the platform, is invited to an event
   Then the volunteer receives an email inviting them to the event

