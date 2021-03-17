Feature: Dashboard
  
# Welcome text

Scenario: Intro text
  Given that I am any logged in user
  When I visit the dashboard
  Then I see the 'Getting the most from…' info column on the right
  
# Talk

Scenario: Latest Talk
  Given that I am any logged in user
  When I visit the dashboard
  Then I see the latest Discourse topics
  
# Groups section
  
Scenario: User has followed a group with an upcoming event
  Given I am a user
  And I have followed at least 1 group that has at least 1 upcoming event
  Then I see a list of the group(s) I follow (orderered alphabetical in the MVP) and the upcoming events (ordered by soonest first) for that/those group(s) in the my groups

Scenario: Host of a group with no upcoming events
  Given I am a host of a group that has no upcoming events
  Then I see a list of my groups and a message encouraging me to add events for my group(s)

  
Scenario: User that hasn't followed any groups
  Given I am a user and I haven’t followed any groups yet
  Then I see a message inviting me to find groups in my area

# Add data section

Scenario: User has RSVPed to at least 1 event
  Given I am any user and I have RSVPed to at least 1 event that has started (or finished)
  When I visit the dashboard
  Then I see the Add Data section

Scenario: User not RSVPed to any events
  Given I am a user that has not RSVPed to any events
  When I visit the dashboard
  Then I do not see the Add Data section

Scenario: Add Data section
  Given I can see the ‘Add Data’ section
  Then the most recent event I have RSVPd to (and organising group) appear pre-selected in the drop down menus
  And the drop down menu for group is sorted alphabetically
  And the drop down menu for events is sorted reverse chronologically (most recent event at the top)

# New groups 

Scenario: New group nearby 
  Given a group has been created in the last month
  And it is within the currently logged in user's area
  And the user is not currently a member of that group 
  Then the count of new groups in your area is incremented by 1

Scenario: Clicking through to new groups
  Given I click on the ‘Newly added:  X groups in your area!’
  Then this takes me through to the /group page with the ‘Groups nearest to you'
  And new groups are flagged with a ‘New’ label
