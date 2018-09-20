Feature: Features/Onboarding
    As a prospective user
    In order to find out about why I should join the Restarters community
    I want to see an easy-to-understand overview of the benefits of joining

Scenario: Unregistered user views onboarding information
    Given the user is unregistered
    When the user visits the features page
    Then the user should be presented with the onboarding text and images

# Registered users should be able to view the onboarding information if they want to.
Scenario: Registered user views onboarding information
    Given the user is registered
    When the user visits the features page
    Then the user should be presented with the onboarding text and images

Scenario: Unregistered user starts sign up process
    Given the user is unregistered
    When the user visits the features page
    And clicks the sign up button
    Then they will land on select skills page 

Scenario: Registered user starts sign up process
    Given the user is registered
    When the user visits the features page
    And clicks the sign up button
    Then they will be shown a message saying 'You are already registered!'
    And they will be taken to the dashboard