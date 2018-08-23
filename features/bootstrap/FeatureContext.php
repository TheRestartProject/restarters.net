<?php

use App\User;
//use Msurguy\Honeypot;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use PHPUnit_Framework_Assert as PHPUnit;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context, SnippetAcceptingContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

// Scernario - Onboarding.feature


    /**
     * @Given the user is unregistered
     */
    public function theUserIsUnregistered()
    {
       // throw new PendingException();

    }

    /**
     * @When the user visits the features page
     */
    public function theUserVisitsTheFeaturesPage()
    {
        //throw new PendingException();
       $this->visit('/about');
    }

    /**
     * @Then the user should be presented with the onboarding text and images
     */
    public function theUserShouldBePresentedWithTheOnboardingTextAndImages()
    {
        //throw new PendingException();
        $this->visit('/about');
        $this->assertPageContainsText('We are a global community of people who help others fix their electronics in community events. Join us!');
    }

    /**
     * @Given the user is registered
     */
    public function theUserIsRegistered()
    {
       // throw new PendingException();
        $this->visit('/dashboard'); 

    }

    /**
     * @When clicks the sign up button
     */
    public function clicksTheSignUpButton()
    {
        //throw new PendingException();
       // $this->assertPageAddress('/about');
        $this->pressButton('Sign me up!');
    }

    /**
     * @Then they will land on select skills page
     */
    public function theyWillLandOnSelectSkillsPage()
    {
       // throw new PendingException();
         $this->assertPageAddress('/user/register');
         $this->assertSee('What skills would you like to share with others?');
    }

    /**
     * @Then they will be shown a message saying :arg1
     */
    public function theyWillBeShownAMessageSaying($arg1)
    {
       // throw new PendingException();
        $this->assertPageAddress('/user/register');
        $this->assertSee('The email has already been taken');
    }

    /**
     * @Then they will be taken to the dashboard
     */
    public function theyWillBeTakenToTheDashboard()
    {
       // throw new PendingException();
        $this->assertPageAddress('/dashboard');
    }


// Scernario - ForgotPassword.feature


    /**
     * @When a user completes the fields as follows
     */
    public function aUserCompletesTheFieldsAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create(); //-- check doc on table node gherkin
    }

    /**
     * @When clicks on reset button
     */
    public function clicksOnResetButton()
    {
        //throw new PendingException();
        $this->visit('/user/recover');
        $this->pressButton('Reset');
    }

    /**
     * @Then user should land on same page with a message saying the please check your email and follow.
     */
    public function userShouldLandOnSamePageWithAMessageSayingThePleaseCheckYourEmailAndFollow()
    {
        //throw new PendingException();
        $this->assertPageAddress('/user/recover');
        $this->assertSee('Email Sent! Please check your inbox and follow instructions');
    }

    /**
     * @When a user enters wrong email id or the email id is not present in database
     */
    public function aUserEntersWrongEmailIdOrTheEmailIdIsNotPresentInDatabase()
    {
        //throw new PendingException();
        $this->fillField('email', 'iopuiopuiop');        
    }

    /**
     * @When clicks reset button
     */
    public function clicksResetButton()
    {
        //throw new PendingException();
        $this->visit('/user/recover');
         $this->pressButton('Reset');
    }

    /**
     * @Then the user lands on same page with an error.
     */
    public function theUserLandsOnSamePageWithAnError()
    {
        //throw new PendingException();
        $this->assertPageAddress('/user/recover');
        $this->assertSee(' This email is not in our database.');
    }

    /**
     * @When a user remembers the password
     */
    public function aUserRemembersThePassword()
    {
        //throw new PendingException();
        $this->visit('/user/recover'); 

    }

    /**
     * @When clicks on the link I remembered. Let me sign in
     */
    public function clicksOnTheLinkIRememberedLetMeSignIn()
    {
        //throw new PendingException();
        //$this->visit('/user/recover');
        $this->clickLink('I remembered. Let me sign in'); 
    }
    

    /**
     * @Then the user lands on login page.
     */
    public function theUserLandsOnLoginPage()
    {
        //throw new PendingException();
         $this->assertPageAddress('/login'); 
    }

    /**
     * @When the user clicks the forgot password link
     */
    public function theUserClicksTheForgotPasswordLink()
    {
       // throw new PendingException();
         $this->visit('/user/recover'); 
         $this->fillField('email', 'fry@planetexpress.com');
         $this->clickLink('Forgot password'); 

    }

    /**
     * @Then the user would receive an email to his registered email account, to reset password.
     */
    public function theUserWouldReceiveAnEmailToHisRegisteredEmailAccountToResetPassword()
    {
        //throw new PendingException();
        
    }


// Scenario - SignIn.feature

    /**
     * @Given the following user accounts have been created
     */
    public function theFollowingUserAccountsHaveBeenCreated(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When a user logs in with email :arg1 and password :arg2
     */
    public function aUserLogsInWithEmailAndPassword($email, $password)
    {
        $user = factory(User::class)->create([
            'email' => $email,
            'password' => Hash::make($password)
        ]);
    }

    /**
     * @Then the user is logged in as :arg1 with email :arg2
     */
    public function theUserIsLoggedInAsWithEmail($arg1, $arg2)
    {
        //throw new PendingException();
        $this->visit('http://127.0.0.1:8000/login');
        //$this->printLastResponse();
        //$this->assertResponseStatus(500);

       // $this->assertPageContainsText('Sign in');
        $this->fillField('email', $arg1);
        $this->fillField('password', $arg2);

        Honeypot::generate('my_name', 'my_time');

        $this->pressButton('Login');
    }

    /**
     * @Then the user is not logged in
     */
    public function theUserIsNotLoggedIn()
    {
        //throw new PendingException();
        $this->assertPageAddress('/login');
    }

    /**
     * @Then a message is displayed to the user letting them know they have not been logged in
     */
    public function aMessageIsDisplayedToTheUserLettingThemKnowTheyHaveNotBeenLoggedIn()
    {
        //throw new PendingException();
        //$this->printLastResponse();
         $this->assertPageAddress('/login');
         $this->assertPageContainsText('Sign in');
         //$this->assertPageContainsText('These credentials do not match our records.');
    }


// Scenario - ResetPassword.feature 


    /**
     * @When a user fills the data as follows
     */
    public function aUserFillsTheDataAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When clicks on change password button
     */
    public function clicksOnChangePasswordButton()
    {
        //throw new PendingException();
        $this->visit('/user/reset');
        // $this->fillField('password', 'abcd123');
        // $this->fillField('repeatpassword', 'abcd123');

        $this->pressButton('Change password');

    }

    /**
     * @Then user should land on login page with a message saying the password has been successfully changed.
     */
    public function userShouldLandOnLoginPageWithAMessageSayingThePasswordHasBeenSuccessfullyChanged()
    {
        //throw new PendingException();
         $this->assertPageAddress('/login');
         $this->assertPageContainsText('Password updated, please login to continue');

    }

    /**
     * @When a user types the password in confirm password field, it should match with password entered before in the password field
     */
    public function aUserTypesThePasswordInConfirmPasswordFieldItShouldMatchWithPasswordEnteredBeforeInThePasswordField()
    {
        //throw new PendingException();
        $this->visit('/user/reset');   
        $pass1 = $this->fillField('password', 'abcd123');
        $pass2 = $this->fillField('confirm_password', 'abcd123');

        if($pass1 == $pass2){
            $this->visit('/login');
        }else{
            $this->visit('/user/reset');
        }


    }

    /**
     * @When the password should be equal to or more than six characters
     */
    public function thePasswordShouldBeEqualToOrMoreThanSixCharacters()
    {
        //throw new PendingException();

    }

    /**
     * @Then the user will be set up with new password and continue to next process.
     */
    public function theUserWillBeSetUpWithNewPasswordAndContinueToNextProcess()
    {
        //throw new PendingException();
        $this->assertPageAddress('/login');
    }


// Scenario - SelectingSkills.feature

    
    /**
     * @Given the user is registering and is on the select skills step
     */
    public function theUserIsRegisteringAndIsOnTheSelectSkillsStep()
    {
        //throw new PendingException();
        $this->visit('/user/register');
       

    }

    /**
     * @When the user selects at least one option from the list of skills
     */
    public function theUserSelectsAtLeastOneOptionFromTheListOfSkills()
    {
       // throw new PendingException();
         $this->pressButton('Publicising events');
          $this->pressButton('Software/OS');

    }

    /**
     * @When click on Next step button
     */
    public function clickOnNextStepButton()
    {
        //throw new PendingException();
         $this->pressButton('Next step');
    }

    /**
     * @Then the user lands on About and Register page
     */
    public function theUserLandsOnAboutAndRegisterPage()
    {
        //throw new PendingException();
        $this->assertPageAddress('/user/register');
        $this->assertPageContainsText('Tell us a little bit about yourself');
    }

    /**
     * @When the user does not select any option from the list of skills
     */
    public function theUserDoesNotSelectAnyOptionFromTheListOfSkills()
    {
        //throw new PendingException();
    }

    /**
     * @When click on Next Step button
     */
    public function clickOnNextStepButton2()
    {
        //throw new PendingException();
         $this->pressButton('Next step');
    }


// Scenario - AboutRegister.feature


    /**
     * @Given the user accounts have not been created yet
     */
    public function theUserAccountsHaveNotBeenCreatedYet()
    {
        //throw new PendingException();

    }

    /**
     * @When a user enters all the data needed as follows
     */
    public function aUserEntersAllTheDataNeededAsFollows(TableNode $table)
    {
        //throw new PendingException();
        // $hash = $table->getRowsHash();
        // $yourname = $hash['yourname'];
        // $age = $hash['age'];
        // $gender = $hash['gender'];
        // $emailaddress = $hash['emailaddress'];
        // $country = $hash['country'];
        // $town_city = $hash['town/town_city'];
        // $password = $hash['password'];
        // $repeatpassword = $hash['repeatpassword'];
        // return new User($yourname, $age, $gender, $emailaddress, $country, $town_city, $password, $repeatpassword);

         $admin = factory(User::class)->states('Administrator')->create();
            
    }

    /**
     * @When clicks on next step button
     */
    public function clicksOnNextStepButton()
    {
        //throw new PendingException();
        $this->visit('/user/register');
        $this->pressButton('Next step');
    }

    /**
     * @Then the user is taken to Email alert preference page
     */
    public function theUserIsTakenToEmailAlertPreferencePage()
    {
        //throw new PendingException();
        $this->assertPageAddress('/user/register');
        $this->assertPageContainsText('How would you like us to keep in touch?');
    }

    /**
     * @When a user wants to go to previous step, click Previous step link
     */
    public function aUserWantsToGoToPreviousStepClickPreviousStepLink()
    {
        //throw new PendingException();
        $this->clickLink('Previous step');
    }

    /**
     * @Then the user lands on previous page i.e., select skills page
     */
    public function theUserLandsOnPreviousPageIESelectSkillsPage()
    {
        //throw new PendingException();
        $this->assertPageAddress('/user/register');
        $this->assertPageContainsText('Tell us a little bit about yourself');
    }

    /**
     * @When a user wants to signup for the application, in the age field there is a restriction of age>=:arg1
     */
    public function aUserWantsToSignupForTheApplicationInTheAgeFieldThereIsARestrictionOfAge($arg1)
    {
        //throw new PendingException();
        $this->$age>=$arg1;
    }

    /**
     * @When the user can select the year from the dropdown
     */
    public function theUserCanSelectTheYearFromTheDropdown()
    {
        //throw new PendingException();
    }

    /**
     * @Then the user can enter the year if greater than or equal to 18.
     */
    public function theUserCanEnterTheYearIfGreaterThanOrEqualTo()
    {
        //throw new PendingException();
    }


// Scenario - EmailPreferences.feature

    /**
     * @When a user wants to get notified by the Restart Project
     */
    public function aUserWantsToGetNotifiedByTheRestartProject()
    {
        //throw new PendingException();
        $this->visit('/user/register');
    }

    /**
     * @When ticking-off the checkbox and click on next step button
     */
    public function tickingOffTheCheckboxAndClickOnNextStepButton()
    {
        //throw new PendingException();
        $this->checkboxChecked('I would like to receive The Restart Project monthly newsletter');
        $this->checkboxChecked('I would like to receive email notifications about events or groups near me');
        $this->pressButton('Next step');
    }

    /**
     * @Then she should land on Data consent page.
     */
    public function sheShouldLandOnDataConsentPage()
    {
        //throw new PendingException();
        $this->assertPageAddress('/user/register');
        $this->assertPageContainsText('Uses of the data you enter');
    }

// Scenario - DataConsent.feature


    /**
     * @When a user gives acceptance to his\/her data to be used by the Restartproject
     */
    public function aUserGivesAcceptanceToHisHerDataToBeUsedByTheRestartproject()
    {
       // throw new PendingException();
         $this->visit('/user/register');
    }

    /**
     * @When ticking-off the checkbox and click on Complete my profile button
     */
    public function tickingOffTheCheckboxAndClickOnCompleteMyProfileButton()
    {
        //throw new PendingException();
        $this->visit('/user/register');
        $this->checkboxChecked();
        $this->checkboxChecked();
        $this->pressButton('Complete my profile');
    }

    /**
     * @Then user should land on dashboard page with pop up of onboarding process.
     */
    public function userShouldLandOnDashboardPageWithPopUpOfOnboardingProcess()
    {
        //throw new PendingException();
        $this->assertPageAddress('/dashboard');
        $this->assertPageContainsText('Welcome!');
    }


// Scenario - CompleteRegistration.feature

    /**
     * @Given the following account have been created as a user
     */
    public function theFollowingAccountHaveBeenCreatedAsAUser(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When a user gets registere themselves on the community platform
     */
    public function aUserGetsRegistereThemselvesOnTheCommunityPlatform()
    {
        //throw new PendingException();
        $this->visit('/dashboard');
    }

    /**
     * @Then an account should be created within the system.
     */
    public function anAccountShouldBeCreatedWithinTheSystem()
    {
        //throw new PendingException();
    }

    /**
     * @When a user creats an account onto  the system
     */
    public function aUserCreatsAnAccountOntoTheSystem()
    {
        //throw new PendingException();
    }

    /**
     * @Then the user would automatically creates an account on Wiki and Discourse with same details
     */
    public function theUserWouldAutomaticallyCreatesAnAccountOnWikiAndDiscourseWithSameDetails()
    {
        //throw new PendingException();
    }

    /**
     * @Then directly login in wiki and discourse.
     */
    public function directlyLoginInWikiAndDiscourse()
    {
        //throw new PendingException();
    }

// Scenario - DashboardFirstVisit_host.feature

    
    /**
     * @Given the following account have been created as a host
     */
    public function theFollowingAccountHaveBeenCreatedAsAHost(TableNode $table)
    {
       // throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When a host lands on dashboard
     */
    public function aHostLandsOnDashboard()
    {
       // throw new PendingException();
        $this->visit('/dashboard');
    }

    /**
     * @Then he would view all the activities that he can do with a journey of updating your profile.
     */
    public function heWouldViewAllTheActivitiesThatHeCanDoWithAJourneyOfUpdatingYourProfile()
    {
        //throw new PendingException();
        //$this->assertPageAddress('/dashboard');
        $this->visit('/dashboard');
    }

    /**
     * @When host lands on dashboard, the getting started column is useful to build your profile
     */
    public function hostLandsOnDashboardTheGettingStartedColumnIsUsefulToBuildYourProfile()
    {
        //throw new PendingException();
        $this->visit('/dashboard');
    }

    /**
     * @Then the host can build his profile by clicking the links and following the process.
     */
    public function theHostCanBuildHisProfileByClickingTheLinksAndFollowingTheProcess()
    {
        //throw new PendingException();
       // $this->assertPageAddress('/dashboard');
        $this->visit('/dashboard');
       // $this->clickLink('Upload photo');
        // $this->clickLink('Add skills');
        // $this->clickLink('Find a group');
        // $this->clickLink('Find an event');
    }

    /**
     * @When host lands on dashboard, he can view Getting started in community repair, How to host an event, Discussion, Wiki and Community news
     */
    public function hostLandsOnDashboardHeCanViewGettingStartedInCommunityRepairHowToHostAnEventDiscussionWikiAndCommunityNews()
    {
        //throw new PendingException();
        $this->visit('/dashboard');
        // $this->assertPageContainsText('Getting Started');
        // $this->assertPageContainsText('Discussion');
        // $this->assertPageContainsText('Upcoming events');
        // $this->assertPageContainsText('Wiki');
        // $this->assertPageContainsText('Getting started in community repair');
        // $this->assertPageContainsText('Community news');
    }

    /**
     * @Then the host should explore(by clicking the links provided) all the categories to get familiar with the platform.
     */
    public function theHostShouldExploreByClickingTheLinksProvidedAllTheCategoriesToGetFamiliarWithThePlatform()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/dashboard');
        $this->visit('/dashboard');       
        $this->clickLink('See all events');
        $this->clickLink('View the materials');
        }

    /**
     * @When host clicks on view the materials link on dashboard
     */
    public function hostClicksOnViewTheMaterialsLinkOnDashboard()
    {
        //throw new PendingException();
        $this->visit('/dashboard');
        $this->clickLink('View the materials');
    }

    /**
     * @Then he will be landed on About the repair in your community category post on Discourse.
     */
    public function heWillBeLandedOnAboutTheRepairInYourCommunityCategoryPostOnDiscourse()
    {
       // throw new PendingException();
         $this->clickLink('Join the discussion');
        $this->assertPageAddress('https://talk.restarters.net/t/community-values/20');
    }

    /**
     * @Then he will be landed on how to run a repair event post on Discourse.
     */
    public function heWillBeLandedOnHowToRunARepairEventPostOnDiscourse()
    {
        //throw new PendingException();

    }

    /**
     * @When host clicks on Join the discussion link on dashboard
     */
    public function hostClicksOnJoinTheDiscussionLinkOnDashboard()
    {
        //throw new PendingException();
        $this->clickLink('Join the discussion');
         $this->visit('https://talk.restarters.net/');
    }

    /**
     * @Then he will be landed on the homepage of the Discourse.
     */
    public function heWillBeLandedOnTheHomepageOfTheDiscourse()
    {
        //throw new PendingException();
    }

    /**
     * @When host clicks on the links in wiki blog on dashboard
     */
    public function hostClicksOnTheLinksInWikiBlogOnDashboard()
    {
       // throw new PendingException();
    }

    /**
     * @Then he will be landed on wiki page of that particular link.
     */
    public function heWillBeLandedOnWikiPageOfThatParticularLink()
    {
        //throw new PendingException();
    }

    /**
     * @Then he will be landed on The Restart Project pages depending on the link.
     */
    public function heWillBeLandedOnTheRestartProjectPagesDependingOnTheLink()
    {
       // throw new PendingException();
    }


// Scenario - InviteRestarters.feature

    /**
     * @Given the following account have been created as an host
     */
    public function theFollowingAccountHaveBeenCreatedAsAnHost(TableNode $table)
    {
        // throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When a user clicks on invite button, invite restarters a pop up screen is displayed
     */
    public function aUserClicksOnInviteButtonInviteRestartersAPopUpScreenIsDisplayed()
    {
        //throw new PendingException();
        $this->visit('/party/view/2#invited');
        //$this->clickLink('Invite to join event');
    }

    /**
     * @When user can check the checkbox so that all the restarters associated in that group will get the invite or host can send invites manually by entering the email address of the restarter as follows
     */
    public function userCanCheckTheCheckboxSoThatAllTheRestartersAssociatedInThatGroupWillGetTheInviteOrHostCanSendInvitesManuallyByEnteringTheEmailAddressOfTheRestarterAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $this->visit('/party/view/2#invited');
        //$this->checkboxChecked();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When also can send an invitation message in the textarea provided as follows
     */
    public function alsoCanSendAnInvitationMessageInTheTextareaProvidedAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When click on send invite button
     */
    public function clickOnSendInviteButton()
    {
         //throw new PendingException();
        $this->visit('/party/view/2#invited');
        //$this->clickLink('Send invites');
    }

    /**
     * @Then host will land on event page with number of invites in the attendace section also a message saying the invites have been sent successfully.
     */
    public function hostWillLandOnEventPageWithNumberOfInvitesInTheAttendaceSectionAlsoAMessageSayingTheInvitesHaveBeenSentSuccessfully()
    {
         //throw new PendingException();
        $this->visit('/party/view/2#invited');
        $this->assertPageContainsText('Attendance');
    }

    /**
     * @When a user gives invalid email id
     */
    public function aUserGivesInvalidEmailId()
    {
        //throw new PendingException();
        $this->visit('/party/view/2#invited');
        $this->fillField('Send invites to', 'gfhigfhkgh');

    }

    /**
     * @When clicks on send invite button
     */
    public function clicksOnSendInviteButton()
    {
       // throw new PendingException();
        $this->visit('/party/view/2#invited');
        $this->clickLink('Send invites');
    }

    /**
     * @Then an error message will display.
     */
    public function anErrorMessageWillDisplay()
    {
        //throw new PendingException();
        $this->visit('/party/view/2#invited');
        $this->assertPageContainsText('Wrong Email address');
    }

    /**
     * @When the user clicks the send invite button
     */
    public function theUserClicksTheSendInviteButton()
    {
        //throw new PendingException();
        $this->visit('/party/view/2#invited');
        $this->clickLink('Send invites');
    }

    /**
     * @Then the volunteer(s) that the user has sent sent invite to an event would receive an email about information on that event.
     */
    public function theVolunteerSThatTheUserHasSentSentInviteToAnEventWouldReceiveAnEmailAboutInformationOnThatEvent()
    {
        //throw new PendingException();
    }


// Scenario - InvitedRestarters.feature

    /**
     * @When a user clicks on see all invited link in the events page
     */
    public function aUserClicksOnSeeAllInvitedLinkInTheEventsPage()
    {
        //throw new PendingException();
        $this->visit('/party/view/2');
        //$this->clickLink('See all confirmed');
    }

    /**
     * @Then a pop up appears with all the list of restarters that have been invited
     */
    public function aPopUpAppearsWithAllTheListOfRestartersThatHaveBeenInvited()
    {
       // throw new PendingException();
        $this->assertPageAddress('/party/view/2');
        //$this->assertPageContainsText('An overview of who attended your event and their skills.');
    }

    /**
     * @Then can view the restarter name with their skills.
     */
    public function canViewTheRestarterNameWithTheirSkills()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/view/2');
      // $this->assertPageContainsText('skills');
    }


// Scenario - ManageActivePastEvent_restarter.feature

    /**
     * @Given the following account have been created a restarter
     */
    public function theFollowingAccountHaveBeenCreatedARestarter(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When a restarter clicks on particular event page
     */
    public function aRestarterClicksOnParticularEventPage()
    {
        //throw new PendingException();
        $this->visit('/party');
        //$this->clickLink('Restart HQ');
    }

    /**
     * @When likes to view environmental impact, attendees, event details etc.,
     */
    public function likesToViewEnvironmentalImpactAttendeesEventDetailsEtc()
    {
        //throw new PendingException();
         $this->visit('/party/view');
        //$this->assertPageContainsText('Environmental impact');
    }

    /**
     * @Then he can see on that particular event page.
     */
    public function heCanSeeOnThatParticularEventPage()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/view');
       // $this->visit('/party/view/1');
    }

    /**
     * @When a restarter who attended the event wants to edit devices section
     */
    public function aRestarterWhoAttendedTheEventWantsToEditDevicesSection()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        //$this->assertPageContainsText('Edit');
    }

    /**
     * @When should click on edit option
     */
    public function shouldClickOnEditOption()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        //$this->clickLink('Edit');
    }

    /**
     * @Then he can view editable options of that device
     */
    public function heCanViewEditableOptionsOfThatDevice()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        //$this->assertPageContainsText('Category');
    }

    /**
     * @Then save the changes by clicking on save button.
     */
    public function saveTheChangesByClickingOnSaveButton()
    {
        //throw new PendingException();
        $this->visit('/party/view');
       // $this->pressButton('Save device');
    }

    /**
     * @When a restarter wants to view the volunteers who have attended that event
     */
    public function aRestarterWantsToViewTheVolunteersWhoHaveAttendedThatEvent()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        //$this->assertPageContainsText('See all attended');
    }

    /**
     * @Then he can view in attendace section of the event page.
     */
    public function heCanViewInAttendaceSectionOfTheEventPage()
    {
        //throw new PendingException();
        //$this->visit('/party/view/1');
        $this->assertPageAddress('/party/view');
       // $this->assertPageContainsText('Attendance');
    }

    /**
     * @When a restarter wants to view the number of volunteers invited to the event
     */
    public function aRestarterWantsToViewTheNumberOfVolunteersInvitedToTheEvent()
    {
        //throw new PendingException();
        //$this->visit('/party/view/1');
        $this->assertPageAddress('/party/view');
    }

    /**
     * @Then restarter can see in invited tab.
     */
    public function restarterCanSeeInInvitedTab()
    {
        //throw new PendingException();
         $this->assertPageAddress('/party/view');
        //$this->assertPageContainsText('Invited');
        //$this->assertPageContainsText('See all attended');
    }

    /**
     * @When a restarter wants to view the devices that hase been fixed, repairable and end of life
     */
    public function aRestarterWantsToViewTheDevicesThatHaseBeenFixedRepairableAndEndOfLife()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/view');
    }

    /**
     * @Then can see the list in the devices section
     */
    public function canSeeTheListInTheDevicesSection()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/view');
        //$this->assertPageContainsText('Devices');

    }

    /**
     * @Given logged in as a restarter who didn't attend the event
     */
    public function loggedInAsARestarterWhoDidntAttendTheEvent()
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When the user is on the edit party devices page
     */
    public function theUserIsOnTheEditPartyDevicesPage()
    {
       //throw new PendingException();
        $this->assertPageAddress('/party/view');
    }

    /**
     * @Then there should be no edit button or add button in the devices section
     */
    public function thereShouldBeNoEditButtonOrAddButtonInTheDevicesSection()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/view');
    }

    /**
     * @Then restarter can view the device only.
     */
    public function restarterCanViewTheDeviceOnly()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/view');
    }


// Scenario - ManageActivePastEvents.feature

    /**
     * @When a host clicks on particular event page
     */
    public function aHostClicksOnParticularEventPage()
    {
       // throw new PendingException();
        $this->visit('/party');
    }

    /**
     * @When likes to either edit or update any changes or see environmental impact, attendees, event details etc.,
     */
    public function likesToEitherEditOrUpdateAnyChangesOrSeeEnvironmentalImpactAttendeesEventDetailsEtc()
    {
        //throw new PendingException();
        $this->visit('/party/view');
    }

    /**
     * @When a host wants to login the absence of volunteers who RSVPed and presence of volunteers who came directly to the event
     */
    public function aHostWantsToLoginTheAbsenceOfVolunteersWhoRsvpedAndPresenceOfVolunteersWhoCameDirectlyToTheEvent()
    {
        //throw new PendingException();
        $this->visit('/party/view');
    }

    /**
     * @When host wants to manage that
     */
    public function hostWantsToManageThat()
    {
        //throw new PendingException();
        $this->visit('/party/view');
    }

    /**
     * @Then he can manage it in the attendace section of the event page
     */
    public function heCanManageItInTheAttendaceSectionOfTheEventPage()
    {
       // throw new PendingException();
        $this->visit('/party/view');
       // $this->assertPageContainsText('Attendance');
    }

    /**
     * @Then can delete or add a volunteer through the links provided.
     */
    public function canDeleteOrAddAVolunteerThroughTheLinksProvided()
    {
        // throw new PendingException();
        $this->visit('/party/view');
       // $this->clickLink('Remove volunteer');
    }

    /**
     * @When a host wants to invite volunteers to the event, can send invite via emails
     */
    public function aHostWantsToInviteVolunteersToTheEventCanSendInviteViaEmails()
    {
        //throw new PendingException();
        $this->visit('/party/view');
       // $this->assertPageContainsText('Invited');
    }

    /**
     * @When he can do this in the attendance section in invites tab
     */
    public function heCanDoThisInTheAttendanceSectionInInvitesTab()
    {
        // throw new PendingException();
        $this->visit('/party/view');
        //$this->assertPageContainsText('Invite to join event');
    }

    /**
     * @Then host can see the number of invites sent to the volunteers in that tab.
     */
    public function hostCanSeeTheNumberOfInvitesSentToTheVolunteersInThatTab()
    {
        //throw new PendingException();
        $this->visit('/party/view');
       // $this->clickLink('See all attended');
    }

    /**
     * @When a host has entered the devices that hase been fixed, repairable and end of life
     */
    public function aHostHasEnteredTheDevicesThatHaseBeenFixedRepairableAndEndOfLife()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        // $this->fillField('Category', '');
        // $this->fillField('Brand', '');
        // $this->fillField('Model', '');
        // $this->fillField('Age', '');
        // $this->fillField('Description of problem/solution', '');
        // $this->fillField('Status', '');
        // $this->fillField('Spare parts', '');
    }

    /**
     * @When host wants to either add\/update a device then click on add button for a new device
     */
    public function hostWantsToEitherAddUpdateADeviceThenClickOnAddButtonForANewDevice()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        $this->pressButton('Add');
    }

    /**
     * @When click on edit link of particular device to be updated and fill the details as  follows
     */
    public function clickOnEditLinkOfParticularDeviceToBeUpdatedAndFillTheDetailsAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $this->visit('/party/view');
        $this->clickLink('Edit');
    }

    /**
     * @Then click on save button
     */
    public function clickOnSaveButton()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        $this->pressButton('Save device');
    }

    /**
     * @Then we can find the new\/ updated device in the list of devices.
     */
    public function weCanFindTheNewUpdatedDeviceInTheListOfDevices()
    {
        //throw new PendingException();
        $this->visit('/party/view');
    }

    /**
     * @When 24hours has passed since an event has finished
     */
    public function hoursHasPassedSinceAnEventHasFinished()
    {
        //throw new PendingException();
    }

    /**
     * @Then the post event device upload reminder email shouldbe sent to the host of the event.
     */
    public function thePostEventDeviceUploadReminderEmailShouldbeSentToTheHostOfTheEvent()
    {
        //throw new PendingException();
    }

    /**
     * @When the host clicks the send email to restarters button
     */
    public function theHostClicksTheSendEmailToRestartersButton()
    {
        //throw new PendingException();
    }

    /**
     * @Then all the restarters that attended the event would receive an email to reminder them to edit device information.
     */
    public function allTheRestartersThatAttendedTheEventWouldReceiveAnEmailToReminderThemToEditDeviceInformation()
    {
        //throw new PendingException();
    }

    /**
     * @When the host\/restarter marks the description of a repair suitable to wiki and clicks save
     */
    public function theHostRestarterMarksTheDescriptionOfARepairSuitableToWikiAndClicksSave()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        $this->checkboxChecked('checkbox');
        $this->pressButton('Save device');
    }

    /**
     * @Then admin would receive an email to view the repair notes.
     */
    public function adminWouldReceiveAnEmailToViewTheRepairNotes()
    {
        //throw new PendingException();
    }
}
