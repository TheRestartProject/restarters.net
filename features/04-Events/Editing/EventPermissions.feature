Feature: Event Permissions

If there are multiple hosts of a group, then if one host creates an event,
then other hosts of the same group should be able to edit the event.

Background:
  Given the following groups:
  | Name           |
  | Hackney Fixers |
  And the following hosts:
  | Name  | Group          |
  | Fry   | Hackney Fixers |
  | Leyla | Hackney Fixers |
  
Scenario: Permission to edit an event
  Given Fry has created the following event:
  | Name    |
  | Big Fix | 
  When Leyla tries to edit the event 'Big Fix'
  Then she is able to do so