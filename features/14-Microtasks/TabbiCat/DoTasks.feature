Feature: Do TabbiCat tasks
  
In order to be able to report on barriers to repair for tablets,
As Restart,
We want to enable users to classify fault types for tablets.

In summary, TabbiCat is much like MobiFix:ORA, but specifically for tablets.

We remove the inline translations of the problem text.
And we remove the need to separate the data by provider.

Background:
  Given there are still TabbiCat records requiring opinions
  
Scenario: Viewing a task
  Given I am any user, logged in or logged out
  When I visit the TabbiCat quest
  Then I am presented with a random record from the ORA data on tablets
  And I see the data provider, brand and model (where known), and the problem text
  And I see a button named 'Translate'
  And I am asked to choose the main fault with the question 'Where is the main fault?'
  
Scenario: Translate problem text
  Given I am any user, logged in or logged out
  When I click/touch the 'Translate' button
  Then I am taken to Google Translate in a new tab
  And Google Translate is prepopulated with the problem text of the task I was looking at

Scenario: Task has enough problem info to make suggestions
  Given I am any user, logged in or logged out
  And the task I am viewing has enough information for TabbiCat to suggest a fault
  Then I see suggestions for the fault type

Scenario: Task does not have enough problem info to make suggestions
  Given I am any user, logged in or logged out
  And the task I am viewing does not have enough information for TabbiCat to suggest a fault
  Then I don't see any section related to fault suggestions

Scenario: Task has relevant fault type to select
  Given I am completing the TabbiCat quest
  When I have selected one of the fault types (e.g. ‘Camera’)
  Then a button showing ‘Go with “Camera”’ appears
  When I click/tab the ‘Go with “Camera”’ button
  Then the result is recorded and I am presented with a new question

Scenario: Task does not have relevant fault type to select
  Given I am completing the TabbiCat quest
  And I can not determine which fault type it is
  When I click/touch the button marked “I don’t know, Fetch another repair'
  Then no result is recorded and I am presented with a new task
