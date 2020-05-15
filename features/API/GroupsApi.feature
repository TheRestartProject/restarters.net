Feature: Groups API

As a repair network
We want to access data about our groups
So that we can display it on our own website

Specifially to being this has been gone for Repair Together.

The groups feed should includes a list of groups for the network:

- group id and name
- group location (value, country, geocoordinaates)
- website
- description
- image url
- list of upcoming parties
- list of past parties

Scenario: Group details are returned
  Given an event exists for my network
  When I access the events feed
  Then I see the event details

Scenario: Deleted events are not included
  Given an event for a group has been deleted
  When I access the groups feed
  Then I do not see the event listed with that group
