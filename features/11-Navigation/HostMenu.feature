Feature: View Menus as Host
    As a Host
    In order to view all the menus
    I should be able to click on the menus on dashboard.

Background:
    Given the following account have been created as a host
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario Outline: Our group and Other menus
    When a host clicks on <menuitem> in the menu 
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
    
Scenario Outline: Reporting and General menus
    When a host clicks on <menuitem> in the menu 
    Then they land on <landingpage> page.

    Examples:
    | menuitem         | landingpage      |
    | Time reporting   | Time reporting   |
    | Events filter    | Events filter    | 
    | Your profile     | Your profile     |
    | Changed pasword  | Changed pasword  |
    | Logout           | Logout           |

Scenario Outline: Events, Devices and Groups menus
    When a host clicks on <menuitem> in the menu 
    Then they land on <landingpage> page.  

    Examples:
    | menuitem   | landingpage  |
    | Events     | Events       |
    | Devices    | Devices      |
    | Groups     | Groups       | 