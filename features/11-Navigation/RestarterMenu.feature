Feature: View Menus as Restarter
    As a Restarter
    In order to view all the menus
    I should be able to click on the menus on dashboard.

Background:
    Given the following account have been created as a restarter
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario Outline: Our group and Other menus
    When a restarter clicks on <menuitem> in the menu 
    Then they land on <landingpage> page.

    Examples:
    | menuitem              | landingpage           |
    | Fixometer             | Fixometer             |
    | Community             | Community             |
    | Restart Wiki          | Restart Wiki          | 
    | The Repair Directory  | The Repair Directory  |
    | The Restart Project   | The Restart Project   |
    | Help                  | Help                  |
    | Welcome               | Welcome               | 
    
Scenario Outline: General menus
    When a restarter clicks on <menuitem> in the menu 
    Then they land on <landingpage> page.

    Examples:
    | menuitem         | landingpage     |
    | Your profile     | Profile         |
    | Changed pasword  | Changed pasword |
    | Logout           | Logout          |

Scenario Outline: Events, Devices and Groups menus
    When a restarter clicks on <menuitem> in the menu 
    Then they land on <landingpage> page.  

    Examples:
    | menuitem   | landingpage  |
    | Events     | Events       |
    | Devices    | Devices      |
    | Groups     | Groups       | 