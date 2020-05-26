Feature: Creating online events

In order to continue supporting repair during coronavirus,
As a host,
I would like to create online events

Online events are not required to have a physical location associated with them.
They will usually have an additional online link associated with them, such as to a place to sign up for a webinar, or a link to an online conference tool.

Scenario: Marking event as online
  Given I have set the event as being online
  When I save the event
  Then the event is saved as an online event

Scenario: Leaving location blank
  Given I have left the location field empty
  When I save the event
  Then I do not encounter any errors
