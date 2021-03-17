Feature: Filtering items

Scenario: Searching by Item & Repair Info
  Given I have expanded the Item & Repair Info filter group
  Then I see Category, Brand, Model or Item Type, Repair Status, ‘Search problem/solution’, and Interesting Case Study

Scenario: Filtering by category
  Given I have clicked the category selector
  Then I see the list of clusters with the categories per cluster
  And a new artificial cluster title is introduced called ‘Other (powered)' with the sole category being ‘Misc (powered)' - this cluster appears above the 'Non-powered items’ cluster

Scenario: Searching by Item & Repair Info
