Feature: Edit user (Account)
    As an Admin
    In order to change the account details entered before
    I should use edit functionality

Scenario: Edit User account
    When an admin changes/updates any account details and clicks on save
    Then he/she should see an pop up message as changes have been saved.

Scenario: Changing Password 
# Change password and click on change password button to save
    When changes are made in the fields as follows and clicks on change password button
    | Current password   | New password   | New repeat password | 
    | jenny              | hello!         | hello!              |
    | diamond            | hi£donna!      | hi£donna!           | 
    Then a pop-up message shows saying all the changes have been saved.

Scenario: Admin only
# Updating details in the Repair skills section
  When the admin uses this page to change a users role and group
  Then only admin can have that privilage to do. 