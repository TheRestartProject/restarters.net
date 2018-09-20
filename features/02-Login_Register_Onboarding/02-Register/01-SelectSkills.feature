Feature: Selecting skills
    As a community organiser
    In order to help group hosts organise their events
    I want volunteers to list their skills when they join

    As a volunteer
    In order to help hosts assign me to tasks during events
    I want to list my skills when I register

Scenario: User selects some skills and clicks next
    Given the user is registering and is on the select skills step
    When the user selects at least one option from the list of skills 
    And click on Next step button
    Then the user lands on About and Register page

# Although useful, selecting skills is optional.
Scenario: User selects no skills and clicks next
    Given the user is registering and is on the select skills step
    When the user does not select any option from the list of skills
    And click on Next Step button
    Then the user lands on About and Register page