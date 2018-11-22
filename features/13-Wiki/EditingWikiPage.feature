Feature: Editing a wiki page
    As a user
    In order to edit a wiki page
    The user should have the wiki badge

Background:
    Given the following user accounts have been created
        | Email                 | Password |
        | fry@planetexpress.com | fry!     | 

Scenario: Editing wiki page
    Given the user has an account in restarters.net and a wiki badge on discourse
    When a user logs in to thier wiki account
    Then they should have the permission to edit the wiki page.
