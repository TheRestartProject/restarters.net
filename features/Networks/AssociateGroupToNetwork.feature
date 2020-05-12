Feature: Associate Group to Network

As an Admin
I want to be able to associate existing groups to a network
So that I can organise existing groups into networks

This is only for Admins.

Network coordinators can't do this (only invite a group to join a network).

Being able to add any group to a network (and therefore get moderation rights and additional permissions over that group) would give network coordinators a lot of power. 

Scenario: Associating when editing a group
  Given I am an Admin
  When I am editing a group
  Then I can amend the repair networks(s) to which this group is associated 

Scenario: Associating from network page
  Given I am an Admin
  When I am viewing a network page
  Then there is an option to add a group to this network 
