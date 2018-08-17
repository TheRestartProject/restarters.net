Feature: Kernel access
  In order to have access to real data
  As a features tester
  I need to have access to the application kernel

  Scenario: Reading and setting kernel parameters
    Given I have a kernel instance
    When I get container parameters from it
    Then there should be "custom_app" parameter
    And it should be set to "behat-test-app" value
    But there should not be "custom2" parameter

  Scenario: Allow handling of container parameters which are collections
    Given I have a kernel instance
    When I get container parameters from it
    Then there should be "collection_param" parameter
    And the value should be an array
    And the array should contain only the values "Param 1,Param 2"