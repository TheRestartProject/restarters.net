Feature: View All Groups
    As a User (host, restarter)
    In order to view all the groups
    I should be able to go to groups page and click on see all groups link

Background:
    Given the following account have been created as an host
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: View all groups
    When a host clicks on see all groups link
    Then he lands on all groups page and can see all the groups in that page.
    
Scenario: Create new group
    When a host wants to create a new group, should click on create new group button
    Then add an group page opens.
    
Scenario: To access group details 
    When a host wants to access/check the group details
    And clicks on the group name link
    Then host lands on that particular group page.
    
Scenario: To check the restarters and hosts
   When a host wants to check who are the hosts and restarters 
    And clicks on the number link under their respective category
    Then host can view the details on a pop up screen.