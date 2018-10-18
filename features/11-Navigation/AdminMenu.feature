Feature: View Menus as an Admin
    As an admin
    In order to view all the menus
    I should be able to click on the menus on dashboard.

Background:
    Given the following account have been created as an admin
        | Email                      | Password |
        | dean@wecreatedigital.co.uk | dean     | 

Scenario Outline: Our group and Other menus with parameters
    When a host clicks on <menuitem> in the menu 
    Then they land on <landingpage> page.

    Examples:
    | menuitem              | landingpage                                     |
    | Discussion            | https://talk.restarters.net/                    |
    | Restart Wiki          | https://therestartproject.org/wiki/Main_Page    |
    | The Repair Directory  | https://therestartproject.org/repairdirectory/  | 
    | The Restart Project   | https://therestartproject.org/                  |
    | Help                  | Help                                            |
    | Welcome               | Welcome                                         | 
    
Scenario Outline: Administrator, Reporting and General menus with parameters
    When a host clicks on <menuitem> in the menu 
    Then they land on <landingpage> page.

    Examples:
    | menuitem         | landingpage      |
    | Brands           | Brands           |
    | Skills           | Skills           |
    | Group tags       | Group tags       | 
    | Categories       | Categories       |
    | Users            | Users            |
    | Roles            | Roles            | 
    | Time reporting   | Time reporting   |
    | Event reporting  | Event reporting  | 
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