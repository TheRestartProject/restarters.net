Feature: Mobifix:ORA  
    As a user (all roles and anonymous)   
    I should be able to view the default Mobifix:ORA page.
    I should be able to see an ORDS record on the default Mobifix:ORA page.
    I should be able to filter the ORDs record by partner.
    I should be able to see optional suggestions for opinions.
    I should be able to select an opinion.
    I should be able to confirm/submit an opinion.
    I should be able to fetch another ORDS record without submitting an opinion.
    I should be able to view the Mobifix:ORA status page.
    I should be able to navigate to the Mobifix:ORA status page.
    I should be able to view the Mobifix:ORA info modal.
    I should be presented with a strategic "Call To Action" (cta) modal.

Background: User may be logged in or anonymous.

Scenario Outline: View default page with ORDS record
    When a user loads the Mobifix:ORA page (default)
    they are presented with an ORDS record with ORDS category "Mobile"
    unless all such records have attained max opinions.

Scenario Outline: View default page with filtered ORDS record
    When a user loads the Mobifix:ORA page (default) with partner query
    they are presented with an ORDS record with ORDS category "Mobile"
    and ORDS partner for <partner> only
    unless all such records have attained max opinions.

Scenario Outline: View status page
    When a user loads the Mobifix:ORA status page
    they are presented with tables of app status information and statistics.

Scenario Outline: View default page and select an opinion
    When a user loads the Mobifix:ORA page (default)
    they are presented with a selection of buttons labelled with "fault types"    
    when they click one they are presented with a button to confirm selection.

Scenario Outline: View default page and see suggestions
    When a user loads the Mobifix:ORA page (default)
    they may be presented with a selection of buttons 
    labelled with suggested "fault types"
    when they click one they are presented with a button to confirm selection.

Scenario Outline: View default page with no available ORDS records
    When a user loads the Mobifix:ORA page
    and all ORDS Mobile records have attained 3 opinions
    they are redirected to the Mobifix:ORA status page 
    with a "thank you" message.    
    
Scenario Outline: Navigate to Mobifix:ORA status page from default page
    When a user clicks on <whale icon>
    then they land on the Mobifix:ORA status page.

Scenario Outline: Navigate to default page from status page
    When a user clicks on <whale icon>
    then they land on the Mobifix:ORA default page.

Scenario Outline: Present "Call to Action"
    After the requisite number of default page loads
    the user should be presented with "Call To Action" (cta) modal
    offering a choice to continue or redirect to action.

Scenario Outline: Navigate to info modal
    When a user clicks on <i icon> from either default or status page
    then they are presented with a modal containing content about Mobifix:ORA.




