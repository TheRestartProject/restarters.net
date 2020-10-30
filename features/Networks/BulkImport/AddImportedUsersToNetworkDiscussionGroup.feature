Feature: Add Imported Users to Network Discussion Group
  
As a new network being imported, 
We'd like all of our imported users to be added to our discussion group
SO THAT (from network perspective)... we can communicate with all of our members easily via Talk
SO THAT (from Restart perspective)… we have more users engaging in discussion on Talk.
  
Each network has its own corresponding network discussion group in Talk (Discourse).

When we do a bulk import of groups and users, we want to add those new users to the discussion group for the network.

# Background

We've done an import for Repair Together only so far.

# Considerations

A decision needs to be made by the network as to when we add all of the imported hosts to the discussion group.

It would be safer not to do it before the network has onboarded them, or at least let them all know it is going to happen, in case they started to receive emails from Discourse before they knew what it was.


Scenario: a random selection of 3 users that were imported from the network, are present in the discussion group
  
  Given the sync of users to Discourse has been run
  When I view the list of users in the discussion group admin section
  Then for 3 random users associated with the network in Laravel, I can see them in the Discourse discussion group


Scenario: the count of users in the network in Laravel matches the count of users in the discussion group 
  Given the sync of users to Discourse has been run
  When I view the list of users in the discussion group admin section
  Then the count of users matches the count of users in the network in Laravel (give or take a few existing users, network coordinators etc)
  
