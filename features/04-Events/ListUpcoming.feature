Feature: List upcoming events

Scenario: No filter
  When I don't filter by anything 
  Then I can see all upcoming events

Scenario: Filter by date from
  When I filter by a from date
  Then I can only see events on or after that date

Scenario: Filter by date to
  When I filter by a to date
  Then I can only see events on or before that date

Scenario: Filter by online
  When I filter by online status
  Then I can only see events that are online
