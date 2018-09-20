Feature: Edit Category
    As a User or an Admin
    In order to change the details of category
    I should be able to do by using a edit category page

Background:
    Given the following account have been created as a user or an admin
        | Email                      | Password |
        | jenny@google.co.uk         | dean1    | 

Scenario: Edit Category
    When the fields are changed/updated in edit category section as follows
    | Category name            | weight(kg) | CO2 Footprint(kg) | Reliability | Category cluster  | Description  |
    | jenny@google.co.uk       | dean1      | 1.34              | good        | Digital telephone | good product |
    And click on save category
    Then she will land on All categories page with the edited category in the list of categories.