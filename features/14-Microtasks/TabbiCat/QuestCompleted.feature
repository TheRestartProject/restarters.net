Feature: TabbiCat Quest completed

Scenario: No tasks needing opinions
  Given I am any user, logged in or logged out
  And all TabbiCat records have the required number of opinions
  When I visit the TabbiCat quest
  Then I am shown the TabbiCat status page
  And see the message 'We're all done here, thanks!' 
  # TODO: probably change this text a bit.
  
