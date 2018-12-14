Feature: Emails that are sent out by the system
   As a user (all roles)  
   In order to organise the platform   
   I should be able to send automated/manual emails to users whenever required.

Background:   
   Given the following account have been created an admin     
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     |

Scenario: Post event automated reminder email to host (4)
   Given a host has received a post event automated email
   When the host clicks on Contribute data button
   Then the host lands on that manage event page.

Scenario: Post event reminder email to volunteers (5)
   Given a volunteer has received a post event device reminder email
   When the volunteer clicks on Contribute data button
   Then the volunteer land on that manage event page.

Scenario: Password reset request email (1)
   Given a user has received a password reset request email
   When the user clicks on Reset password button
   Then the user land on the password rest page.

Scenario: Email invitation to group by a user to a existing volunteer (2)
   Given a existing volunteer has received an email invitation to group
   When the existing volunteer clicks on Join group button
   Then the volunteer land on that event page with a successful message on top.

Scenario: Email invitation to group by a user to a new volunteer (2)
   Given a new volunteer has received an email invitation to group
   When the new volunteer clicks on Join group button
   Then the volunteer land on registeration page, go through registration process
   And should land on that group page with a welcome message.

 Scenario: Email invitation to an event by existing user to a existing volunteer (3)
   Given existing volunteer has received an email invitation to an event
   When the existing volunteer clicks on Read more button
   Then the volunteer land on that particular event page.  

 Scenario: Email Notification about event creation to admin (9)
   Given admin has received an email notification about an event to moderate
   When the admin clicks on View event button
   Then the admin land on that particular edit event page, clicks on approve button. 

 Scenario: Email Notification about group creation to admin (missing wireframe)
   Given admin has received an email notification about a group has been created
   When the admin clicks on View group button
   Then the admin land on that particular group page.

 Scenario: Account created by admin to a new volunteer (13)
   Given a new volunteer has received an email to set password
   When the new volunteer clicks on Set password button
   Then the volunteer land on password reset page.  

Scenario: Email notification to admin by a host/restarter when description of a repair has been marked suitable for wiki (12)
   Given admin has received an email notification about repair description to wiki
   When the admin clicks on View repair notes button
   Then the admin land on edit device page.

Scenario: Email notification to host by admin when event has been approved (10)
   Given host has received an email notification about event confirmation
   When the host clicks on View event button
   Then the host land on Upcoming event page or therestartproject.org(if clicked the link in the email).

Scenario: Email notification to host by new volunteer when he joined the group (8)
   Given host has received an email notification about a volunteer joined the group
   When the host clicks on Go to group button
   Then the host land on that view group page.

Scenario: Email notification to host by volunteer when he has sent an RSVP (7)
   Given host has received an email notification about a volunteer attending the event
   When the host clicks on View your event button
   Then the host land on upcoming event page.

Scenario: Admin can select the type of emails he/she would like to receive
   Given admin wants to select the type of emails
   When the user clicks on the checkboxes in the preferences section
   Then the user wil get emails accordingly.

Scenario: Admin receives email when abnormal number of Misc devices are added
   When user enters abnormal number of misc devices
   Then the admin gets an email about the scenario.