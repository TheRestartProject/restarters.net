Feature: Brand name 
    As an admin
    In order to view all the brand names in one page 
    I should be able to do that by navigating to brand page

Background:
    Given the following account have been created as an admin/user
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: View All Brands
    When an admin navigate to Brand page       
    Then she can view all the brand names in that page.

Scenario: Creating new brand
    When an admin wants to create a new brand
    Then he/she should click on create new brand button.