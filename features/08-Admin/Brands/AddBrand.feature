Feature: Add Brand
    As an admin
    In order to add a brand
    I should be able to do that by filling the fields of add brand pop-up and click on create new brand button

Scenario: Adding new brand
    When a new brand name is added, to do so fill the field as follows and Click on Create new brand button to save the changes
    | Brand name       | 
    | TP-Link          |                                             
    Then you will land on All brands page with newly added brand in the list and also with a message that your brand is added.