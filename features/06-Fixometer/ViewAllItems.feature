Feature: View list of items

Background:
  Given I am any user, looking at the Repair Records section of the Fixometer

Scenario: View all items  
   Then I can see a tab for powered items and a tab for unpowered items
   And for powered items I can see 'Category', 'Brand', 'Model', 'Assessment', 'Group', 'Status', and 'Date'
   And for unpowered items I can see 'Category', 'Item Type', 'Assessment', 'Group', 'Status', and 'Date'
   And the items are ordered by date descending 

Scenario: Expanding result
  When I click on an item then it expands to display the expanded view of the item as per the designs

Scenario: Pagination
  When I click on a page number in either the powered or unpowered tab
  Then the table advances to that page within that tab

Scenario: Sorting
  When I click on one of the column headings
  Then the ordering of the table is ordered by that column (cycling through ascending and descending)

