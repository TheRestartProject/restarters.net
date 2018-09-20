Feature: Edit user (Profile)
    As an Admin
    In order to change the details entered before
    I should be using a edit user functionality

Background:
    Given the following account have been created as a user
        | Email                      | Password |
        | jenny@google.co.uk         | dean1    | 

Scenario: Edit User
    When a user wants to change/update any details 
    And he/she should be able to do that by changing the details and saving them
    Then she should land on the Users page with the edited user in the list of users, a message saying that the changes have been saved .

Scenario: Editing User Profile
# Updating details in the User Profile section and click on save profile button
    When a user enter details in User Profile section as follows and clicks on save profile
    | Name       | Email address          | Age | Country         | Town/City   | Gender  | Your biography(optional)     | 
    | jenny      | jenny@gmail.com        | 45  | United Kingdom  | Remakery    | Male    | I am an Artist by proffesion |
    | diamond    | diamond@gmail.com      | 23  | Spain           | Belgium     | Male    |                              |
    And the user saves all the changes he made in that section
    Then she should land on the profile page with a message saying that the changes have been saved.

Scenario: Editing Repair Skills
# Updating details in the Repair skills section
   When a user types the skills he/she have 
    | Key Skills      | 
    | Mobiles devices |
    | Laptops         |
    | Kitchen devices |
    And the user saves the changes in that section
    Then she should land on the profile page with a message saying that the changes have been saved.

Scenario: Upload profile picture
# Updating the profile picture in change photo section
   When a user wants to change their profile picture
   And browse the pic and click on change photo button
   Then she should land on profile page with the uploaded picture in the placeholder, with a message saying the picture has been uploaded. 