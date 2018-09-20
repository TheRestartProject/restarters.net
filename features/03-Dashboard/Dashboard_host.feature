Feature: View of Dashboard after log in on the community platform
    As a Host
    In order to view the dashboard
    I should be able to login as a host on the community platform.

Background:
    Given the following account have been created as a host
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: View Dashboard
    When a host lands on dashboard 
    Then he would view all the activities that he can do and that is going on and that has been done on the platform.
    
Scenario: Activities present on dashboard
    When host lands on dashboard, he can view Creat new event, Your recent events, How to host an event, Restarters in your area, Discussion, wiki and Community news
    Then the host should be able to navigate(by clicking the links provided) through categories according to their use.
    
Scenario: Host clicks on create new event link on create new event blog on dashboard
    When host clicks on create new event link on dashboard
    Then he will be landed on create new event page.
   
Scenario: Host clicks on Your recent events links on Your recent events blog on dashboard
    When host clicks on see all events link or on a particular event link on dashboard
    Then he will be landed on all events page or on that particular event page respectively.

Scenario: Host clicks on view the materials link on how to host an event blog on dashboard
    When host clicks on view the materials link on dashboard
    Then he will be landed on How to run a repair event post on Discourse.
   
Scenario: Host clicks on Restarters in your area blog on dashboard
#to be developed

Scenario: Host clicks on Join the discussion link on Discussion blog on dashboard
    When host clicks on Join the discussion link on dashboard
    Then he will be landed on the homepage of the Discourse.

Scenario: Host clicks on any links in Wiki blog on dashboard
    When host clicks on the links in wiki blog on dashboard
    Then he will be landed on wiki page of that particular link.

Scenario: Host clicks on any links in the community news on dashboard
    When host clicks on the links in wiki blog on dashboard
    Then he will be landed on The Restart Project pages depending on the link.