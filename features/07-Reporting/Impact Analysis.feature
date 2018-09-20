Feature: Group Impact Analysis
    As a Host
    In order to be able to showcase the work of my Group
    I want to see an impact analysis of my Group's Events

Group hosts are able to get reports about the impact of individual Restart
Parties: total of waste prevented, CO2 emissions prevented, hours
volunteered.

Group hosts are also able to get an an aggregate total of all waste
prevented, CO2 emissions prevented, hours volunteered for the
entirety of their group’s events.

TODO: Add larger dataset test and checking the algorithms.

Background:
    Given the following groups:
        | Id | Name           |
        | 1  | Hackney Fixers |
    And the following events:
        | Id | Location                        | Date       |
        | 1  | The Redmond Centre, Manor House | 28/01/2017 |
        | 2  | Homerton Library                | 19/11/2016 |

Scenario: Impact analysis for event
    Given the following devices logged for the 'Homerton Library' event:
        | Id | Category      | Comment          | Brand | Model   | Repair Status | Spare Parts? |
        | 1  | Laptop medium | Needs new screen | Apple | Mac Air | Repairable    | Yes          |
    When viewing the stats for the 'Homerton Library' event
    Then the stats should be:
        | Participants | Restarters | CO2 Emissions Prevented | Fixed | Repairable | Dead |
        | 35           | 5          | 914kg                   | 12    | 21         | 5    |

Scenario: Impact analysis for group
    When viewing the stats for the 'name of the group' group
    Then the stats should be: