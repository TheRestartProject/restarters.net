Feature: New Groups
    As an Admin
    In order to view the new groups on the wordpress site(main website - https://therestartproject.org/)
    I should be able to do it through API call.

Scenario: View created groups
    When an admin approves a group
    Then they would see the approved group in the list of groups on the wordpress site
    And a group page is created on the wordpress site.

Scenario: View edited upcoming group
    When an admin/host edits an approved group
    Then they would see the edited group in the list of groups on the wordpress site
    And the changes made would appear on the group page created in wordpress site.

Scenario: Delete groups
    When an admin/host deletes an approved group
    Then they will not see the deleted group in the list of groups on the wordpress site
    And the group page created in wordpress site will also be deleted.