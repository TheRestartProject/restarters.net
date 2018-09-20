Feature: View All Groups
    As an admin
    In order to view all the groups
    I should be able to go to groups page and click on see all groups link

Background:
    Given the following account have been created as an admin
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: View all groups
    When an admin clicks on all groups link from the admin drop down
    Then he lands on all groups page and can see all the groups
    And can even search for group.

 Scenario: Search for groups
    When an admin wants to search group, should enter the fields provided in the By details category as follows
    | Name     | Tag        | Town/City    | Country |
    | dean12   | Example1   | London       | UK      |
    |          |            | London       |         |
    |          |            |              | UK      |
    |          | Example2   |              |         |
    | James    |            |              |         |
    And the fields here are optional, so can search by only country or only name etc.,
    Then the admin can view the filtered group. 
    
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