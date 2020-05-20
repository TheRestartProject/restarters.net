Feature: Networks API

Get stats about a particular network via the API.

Scenario: Get stats about network
  Given I have permissions on the Restarters network
  When I call the API for the Restarters network
  Then I get the following stats for the Restarters network
