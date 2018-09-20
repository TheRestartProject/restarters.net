Feature: View of Dashboard after log in on the community platform
    As a Restarter
    In order to view the dashboard
    I should be able to login as a restarter on the community platform.

Background:
    Given the following account have been created as a restarter
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: View Dashboard
    When a restarter lands on dashboard 
    Then he would view all the activities that he can do and that is going on and that has been done on the platform.
    
Scenario: Activities present on dashboard
    When restarter lands on dashboard, he can view Discussion, Upcoming events, Getting started in community repair, Your recent events, Wiki and Community news
    Then the restarter should be able to navigate(by clicking the links provided) through categories according to their use.

Scenario: Restarter clicks on Join the discussion link on Discussion blog on dashboard
    When restarter clicks on Join the discussion link on dashboard
    Then he will be landed on the homepage of the Discourse.

Scenario: Restarter clicks on see all events link on Upcoming events blog on dashboard
    When restarter clicks on see all events link on dashboard
    Then he will be landed on view all events page.

Scenario: Restarter clicks on view the materials link on Getting started in community repair blog on dashboard
    When restarter clicks on view the materials link on dashboard
    Then he will be landed on community values post on Discourse.

Scenario: Restarter clicks on Your recent events links on Your recent events blog on dashboard
    When restarter clicks on see all events link or on a particular event link on dashboard
    Then he will be landed on all events page or on that particular event page respectively.

Scenario: Restarter clicks on any links in Wiki blog on dashboard
    When restarter clicks on the links in wiki blog on dashboard
    Then he will be landed on wiki page of that particular link.

Scenario: Restarter clicks on any links in the community news on dashboard
    When restarter clicks on the links in wiki blog on dashboard
    Then he will be landed on The Restart Project pages depending on the link.