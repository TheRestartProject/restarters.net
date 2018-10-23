Feature: View Your Groups
    As a User (All roles)
    In order to view all the groups that a user is involved and other groups that are near user
    I should be able to go to groups page

Background:
    Given the following account have been created as an host
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: View all groups
# View all gropus i.e., groups involved with and the groups that are near me
    When a host clicks on group page
    Then he lands on group page and can see all the groups in that page
    And one section is the list of groups that host is involved
    And other section is the list of groups that are near to the host along with a see all groups link.
    
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