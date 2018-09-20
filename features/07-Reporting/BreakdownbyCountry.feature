Feature: Breakdown by country - Total hours volunteered  
   As a user (all roles)  
   In order to see the total time volunteered country wise   
   I should be able to click on see all results link in breakdown by country section on time volunteered page.

Background:   
   Given the following account have been created a restarter       
   | Email                      | Password |       
   | dean@wecreatedigital.co.uk | dean     | 

Scenario: Total time volunteered country wise
   When a restarter wants to see the total time volunteered country wise, click on see all results link in breakdown by country section
   Then a pop up appears with all the country names and the time volunteered in the countries.

Scenario: Click on cancel
   When a restarter wants to go back to time volunteered page, click on Cancel
   Then the restarter will go back to time volunteered page.