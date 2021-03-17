Feature: View global impact
  
In order to feel enthused about the importance of repair
As a repair volunteer
I want to see the global impact of repairs within Restarters

Background:
  Given I am viewing the Fixometer page
  # Navigate to https://restarters.dev/fixometer

Scenario: Stats boxes
  Then a CO2 emissions stats box shows the CO2 prevented for powered items only
  And the consumption figure shows the equivalent consumption of km driven
  And a stats box for waste prevented shows waste prevented for powered and unpowered items
  And a stats box for powered items shows the number of repaired powered items
  And a stats box for unpowered items shows the number of repaired unpowered items
  And a stats box for participants shows the total number of participants
  And a stats box for hours volunteered shows the total number of hours volunteered

Scenario:
  Given a group has added items to the most recent event in the Fixometer
  Then the waste weight prevented by the event's fixed items is displayed in the Latest Data card
  And the figure includes both unpowered and powered items
  And the text changes to '<group name> just prevented X kg of waste!'
  And clicking the group name goes to the group
  And clicking the figure for the weight goes to the event at which the waste was prevented

![](./our-global-impact.png)
