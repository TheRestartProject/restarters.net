Feature: View of Dashboard for the first time when a host sign up on the community platform
    As a Host
    In order to view the dashboard
    I should be able to signup as a host on the community platform.

Background:
    Given the following account have been created as a host
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario: View Dashboard
# View dashboard consisting all the activities to bo done on the platform
    When a host lands on dashboard 
    Then he would view all the activities that he can do with a journey of updating your profile.
    
Scenario: About Getting started
    When host lands on dashboard, the getting started column is useful to build your profile
    Then the host can build his profile by clicking the links and following the process.
    
Scenario: Activities present on dashboard
    When host lands on dashboard, he can view Getting started in community repair, How to host an event, Discussion, Wiki and Community news
    Then the host should explore(by clicking the links provided) all the categories to get familiar with the platform.

Scenario: Host clicks on view the materials link on Getting started in community repair blog on dashboard
    When host clicks on view the materials link on dashboard
    Then he will be landed on About the repair in your community category post on Discourse.

Scenario: Host clicks on view the materials link in How to host an event blog on dashboard
    When host clicks on view the materials link on dashboard
    Then he will be landed on how to run a repair event post on Discourse.
   
Scenario: Host clicks on Join the discussion link on Discussion blog on dashboard
    When host clicks on Join the discussion link on dashboard
    Then he will be landed on the homepage of the Discourse.

Scenario: Host clicks on any links in Wiki blog on dashboard
    When host clicks on the links in wiki blog on dashboard
    Then he will be landed on wiki page of that particular link.

Scenario: Host clicks on any links in the community news on dashboard
    When host clicks on the links in wiki blog on dashboard
    Then he will be landed on The Restart Project pages depending on the link.