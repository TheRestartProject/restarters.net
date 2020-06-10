Feature: Add Users To Network Discussion Group

As a network coordinator
I want group members automatically added to our discussion group
SO THAT we are able to easily facilitate discussions between all of the users in our network.

Scenario: User joins group in network
  Given I am a network coordinator
  And there is a discussion group in place in Talk for my network
  When a Restarter follows a repair group in my network
  Then the Restarter is also added to my network discussion group in Talk

Scenario: User joins groups in multiple networks
  Given I am a Restarter
  And I am already a member of discussion groups on Talk
  When I join a repair group in a particular repair network
  Then I am added to the repair network discussion group
  And I am still a member of the other discussion groups 

Scenario: User joins group not in any specific network
  Given the group is in only the generic Restarters network
  When someone joins the group
  Then they are not added to any new specific network discussion group
