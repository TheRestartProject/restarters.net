Feature: Misccat  
    As a user (all roles and anonymous)   
    I should be able to view the default Misccat page.
    I should be able to see a Devices record on the default Misccat page.
    I should be able to select an opinion.    
    I should be able to confirm/submit an opinion.
    I should be able to load another record without submitting an opinion.
    I should be able to view the Misccat status page.
    I should be able to navigate to the Misccat status page.
    I should be able to view the Misccat info modal.
    I should be presented with a strategic "Call To Action" (cta) modal.

Background: User may be logged in or anonymous.

Scenario Outline: View default page with a Devices record
    When a user loads the Misccat page (default)
    they are presented with a Devices record with Fixometer category "Misc"
    unless all such records have attained max opinions.

Scenario Outline: View status page
    When a user loads the Misccat status page
    they are presented with tables of app status information and statistics.

Scenario Outline: View default page and select an opinion
    When a user loads the Misccat page (default)
    they are presented with a selection of buttons labelled with "categories"    
    when they click one they are presented with a button to confirm selection.

Scenario Outline: View page with no available device records
    When a user loads the Misccat page
    and all Misccat records have attained max opinions
    they should not be presented with an error.
#ToDo    they are redirected to the Misccat status page with a "thank you" message.    
    
Scenario Outline: Navigate to status page from devices page
    When a user clicks on <cat icon>
    then they land on the Misccat status page.

Scenario Outline: Navigate to default page from status page
    When a user clicks on <cat icon>
    then they land on the Misccat default page.

Scenario Outline: Present "Demographics"
    After the requisite number of default page loads
    the user should be presented with "Call To Action" (cta) modal
    offering a choice to continue or redirect to action.

Scenario Outline: Navigate to info modal
    When a user clicks on <i icon> from either default or status page
    then they are presented with a modal containing content about Misccat.
