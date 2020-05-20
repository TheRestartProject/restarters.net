Feature: Events API

As a repair network
We want to access data about our events
So that we can display it on our own website

Specifially to being this has been gone for Repair Together.

The event feed should includes:

- event id and event name
- event location and geoordinates
- event description
- details about the group the event was organised by
- the event date, start time and end time
- event impact

Scenario: Event details are returned
  Given an event exists for my network
  When I access the events feed
  Then I see the event details

Scenario: Event update date is included
  Given an event exists for my network
  And an amendment is made to that event
  When I access the events feed
  Then I see the updated date

Scenario: Filtering by dates

Scenario: Deleted events are not included
  Given an event has been deleted
  When I access the events feed
  Then I do not see the deleted event
