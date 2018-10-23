Feature: View of Dashboard for the first time when a restarter sign up on the community platform
    As a Restarter
    In order to view the dashboard
    I should be able to signup as a restarter on the community platform.

Background:
    Given the following account have been created as a restarter
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: View Dashboard
# View dashboard consisting all the activities to bo done on the platform
    When a restarter lands on dashboard 
    Then he would view all the activities that he can do with a journey of updating your profile.
    
Scenario: About Getting started
    When restarter lands on dashboard, the getting started column is useful to build your profile
    Then the restarter can build his profile by clicking the links and following the process.
    
Scenario: Activities present on dashboard
    When restarter lands on dashboard, he can view Discussion, Getting started in community repair, Upcoming events, Wiki and Community news
    Then the restarter should explore(by clicking the links provided) all the categories to get familiar with the platform.

Scenario: Restarter clicks on Join the discussion link on Discussion blog on dashboard
    When restarter clicks on Join the discussion link on dashboard
    Then he will be landed on the homepage of the Discourse.

Scenario: Restarter clicks on view the materials link on Getting started in community repair blog on dashboard
    When restarter clicks on view the materials link on dashboard
    Then he will be landed on community values post on Discourse.

Scenario: Restarter clicks on see all events link on Upcoming events blog on dashboard
    When restarter clicks on see all events link on dashboard
    Then he will be landed on view all events page.

Scenario: Restarter clicks on any links in Wiki blog on dashboard
    When restarter clicks on the links in wiki blog on dashboard
    Then he will be landed on wiki page of that particular link.

Scenario: Restarter clicks on any links in the community news on dashboard
    When restarter clicks on the links in wiki blog on dashboard
    Then he will be landed on The Restart Project pages depending on the link.