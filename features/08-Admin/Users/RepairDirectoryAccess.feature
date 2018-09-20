Feature: Access to Repair Directory Admin

Only certain users should have a menu item that links through to the Repair Directory Admin section.
This is a user-level permission (not role-based) that can only be set by an Admin.

Scenario: Admin can set repair directory admin permission for another user
  Given I am logged in as an Admin
  When I visit the user account page for another user
  Then I should see the permissions section for setting repair directory admin access

Scenario: Host cannot set repair directory admin permission for themselves
  Given I am logged in as a Host
  When I visit my account editing page
  Then I should not see the permissions section for setting repair directory admin access

Scenario: Admin sets repair directory admin permission for themself
  Given I am logged in as an Admin
  When I visit the user account page for another user
  And I set the Repair Directory Link permission on the user
  Then the user should now have Repair Directory Link permission
  And should see the Repair Directory menu item in the top left menu
# Pass

Scenario: Admin sets repair directory admin permission for another user
  Given I am logged in as an Admin
  When I visit the user account page for another user
  And I set the Repair Directory Link permission on the user
  Then the user should now have Repair Directory Link permission
  And should see the Repair Directory menu item in the top left menu
# Fail
# It doesn't persist the change to the setting when it's for another user