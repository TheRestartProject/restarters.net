<?php

use App\User;
//use Msurguy\Honeypot;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Driver\Selenium2Driver;
use PHPUnit_Framework_Assert as PHPUnit;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context, SnippetAcceptingContext
{
    use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;

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


// Scenario - RecordVolunteer.feature

    /**
     * @When a user clicks on add volunteer button, a pop up screen of add volunteer is displayed
     */
    public function aUserClicksOnAddVolunteerButtonAPopUpScreenOfAddVolunteerIsDisplayed()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        //$this->clickLink('Add volunteer');
    }

    /**
     * @When fill in the fields as follows
     */
    public function fillInTheFieldsAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $this->visit('/party/view');
        // $this->fillField('Group member', 'Not registered on fixometer');
        // $this->fillField('Full name', 'Nora');
        // $this->fillField('Volunteers email address (optional):', 'nora@fdg.com');
    }

    /**
     * @When click on volunteer attended button
     */
    public function clickOnVolunteerAttendedButton()
    {
       // throw new PendingException();
        $this->visit('/party/view');
        //$this->pressButton('Volunteer attended');
    }

    /**
     * @Then host will land on event page with the added volunteer in the list of volunteers attended with a message saying the volunteer has bee successfully recorded.
     */
    public function hostWillLandOnEventPageWithTheAddedVolunteerInTheListOfVolunteersAttendedWithAMessageSayingTheVolunteerHasBeeSuccessfullyRecorded()
    {
       // throw new PendingException();
       //$this->visit('/party/view');
       $this->assertPageAddress('/party/view');
       //$this->assertPageContainsText('Volunteer has successfully been added to event');

    }

    /**
     * @When a user gives invalid group name
     */
    public function aUserGivesInvalidGroupName()
    {
        //throw new PendingException();
        $this->visit('/party/view');
    }


// Scenario - RestartersAttended.feature
    
    /**
     * @When a user clicks on see all attended link in the events page
     */
    public function aUserClicksOnSeeAllAttendedLinkInTheEventsPage()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        //$this->pressButton('See all attended');
    }

    /**
     * @Then a pop up appears with all the list of restarters that have attended
     */
    public function aPopUpAppearsWithAllTheListOfRestartersThatHaveAttended()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/view');
       //$this->assertPageContainsText('All restarters attended');
    }

    /**
     * @Then can view the host of that party
     */
    public function canViewTheHostOfThatParty()
    {
       // throw new PendingException();
        $this->assertPageAddress('/party/view');
       // $this->assertPageContainsText('Host');
    }

    /**
     * @Then can view the restarter name with their skills and also a link to remove the volunteer.
     */
    public function canViewTheRestarterNameWithTheirSkillsAndAlsoALinkToRemoveTheVolunteer()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/view');
        // $this->assertPageContainsText('Restarter');
        // $this->assertPageContainsText('Skills');
        // $this->assertPageContainsText('Remove volunteer');
    }


// Scenario - UpcomingEvent_restarter.feature

    /**
     * @Given the following account have been created as a restarter
     */
    public function theFollowingAccountHaveBeenCreatedAsARestarter(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When a restarter wants to view the upcoming event details- event address, description, attendance
     */
    public function aRestarterWantsToViewTheUpcomingEventDetailsEventAddressDescriptionAttendance()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        // $this->assertPageContainsText('Event details');
        // $this->assertPageContainsText('Description');
        // $this->assertPageContainsText('Attendance');
    }

    /**
     * @Then he can see on the upcoming event page.
     */
    public function heCanSeeOnTheUpcomingEventPage()
    {
        //throw new PendingException();
        //$this->visit('/party/view');
        $this->assertPageAddress('/party/view');
    }

    /**
     * @When a restarter wants to attend the party and wants add to calendar
     */
    public function aRestarterWantsToAttendThePartyAndWantsAddToCalendar()
    {
        //throw new PendingException();
    }

    /**
     * @Then click on add to calendar button
     */
    public function clickOnAddToCalendarButton()
    {
        //throw new PendingException();
    }

    /**
     * @Then the event will be added to your calendar.
     */
    public function theEventWillBeAddedToYourCalendar()
    {
        //throw new PendingException();
    }

    /**
     * @When the volunteer clicks the RSVP button
     */
    public function theVolunteerClicksTheRsvpButton()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/view');
        //$this->pressButton('RSVP');
    }

    /**
     * @Then the host(s) would receive an email about status of the volunteer.
     */
    public function theHostSWouldReceiveAnEmailAboutStatusOfTheVolunteer()
    {
        //throw new PendingException();
    }


// Scenario - ShareStats.feature

    /**
     * @When a user wants to share their stats to other places, click on Events stats embed button
     */
    public function aUserWantsToShareTheirStatsToOtherPlacesClickOnEventsStatsEmbedButton()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        //$this->pressButton('Share event stats');

    }

    /**
     * @When a share your stats from this event pop up screen is displayed along with an infogrpahic
     */
    public function aShareYourStatsFromThisEventPopUpScreenIsDisplayedAlongWithAnInfogrpahic()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        // $this->isSelected('Headline stats');
        // $this->isSelected('CO2 equivalence visualisation');
    }

    /**
     * @When copy the links required and use them
     */
    public function copyTheLinksRequiredAndUseThem()
    {
        //throw new PendingException();
        $this->visit('/party/view');
    }

    /**
     * @When click on cancel symbol
     */
    public function clickOnCancelSymbol()
    {
        //throw new PendingException();
    }

    /**
     * @Then the user will be back on events page.
     */
    public function theUserWillBeBackOnEventsPage()
    {
        //throw new PendingException();
        $this->visit('/party/view');
    }


// Scenario - EditEvent.feature
    
    /**
     * @Given the following account have been created as a host or an admin
     */
    public function theFollowingAccountHaveBeenCreatedAsAHostOrAnAdmin(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When a host clicks on edit event page and changes\/updates the data as follows
     */
    public function aHostClicksOnEditEventPageAndChangesUpdatesTheDataAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $this->visit('/party/edit');
        // $this->fillField('Name of event', 'sdfgdfg');
        // $this->isSelected('Event group', 'fgdfg');
        // $this->fillField('Description', 'dfg');
        // $this->fillField('Date of event', '23/4/2018');
        // $this->fillField('Start/end time', 'fg');
        // $this->fillField('Venu address', '22, sefsdfgdfg');
    }

    /**
     * @When clicks on save party button
     */
    public function clicksOnSavePartyButton()
    {
        //throw new PendingException();
        $this->visit('/party/edit');
       //$this->pressButton('Save event');
    }

    /**
     * @Then host lands on all events page with the edited event in the list of events.
     */
    public function hostLandsOnAllEventsPageWithTheEditedEventInTheListOfEvents()
    {
        //throw new PendingException();
    }

    /**
     * @When a host copies and paste into the description box
     */
    public function aHostCopiesAndPasteIntoTheDescriptionBox()
    {
       // throw new PendingException();
    }

    /**
     * @When the data should loose all htmls and css properties it has
     */
    public function theDataShouldLooseAllHtmlsAndCssPropertiesItHas()
    {
       // throw new PendingException();
    }

    /**
     * @Then it show a message inside description box as text cleaned.
     */
    public function itShowAMessageInsideDescriptionBoxAsTextCleaned()
    {
        //throw new PendingException();
    }

    /**
     * @When a host clicks on date field, calendar should pop up
     */
    public function aHostClicksOnDateFieldCalendarShouldPopUp()
    {
       // throw new PendingException();
    }

    /**
     * @When select a date when to arrange party
     */
    public function selectADateWhenToArrangeParty()
    {
       //throw new PendingException();
    }

    /**
     * @Then host lands on the same page and continues with next process.
     */
    public function hostLandsOnTheSamePageAndContinuesWithNextProcess()
    {
        //throw new PendingException();
    }

    /**
     * @When a host clicks on start time, automatically from then +3hr time is calculated as follows
     */
    public function aHostClicksOnStartTimeAutomaticallyFromThenHrTimeIsCalculatedAsFollows(TableNode $table)
    {
       // throw new PendingException();
    }

    /**
     * @Then that time is stored in the end time field.
     */
    public function thatTimeIsStoredInTheEndTimeField()
    {
       // throw new PendingException();
    }

    /**
     * @When a host clicks on venue address, types the address
     */
    public function aHostClicksOnVenueAddressTypesTheAddress()
    {
       // throw new PendingException();
        $this->visit('/party/edit');
        $this->fillField('Venue address');
    }

    /**
     * @Then automatically suggestions should show up and the place should be pointed in map.
     */
    public function automaticallySuggestionsShouldShowUpAndThePlaceShouldBePointedInMap()
    {
        //throw new PendingException();
    }

    /**
     * @When user clicks on add image text, then file explorer should open
     */
    public function userClicksOnAddImageTextThenFileExplorerShouldOpen()
    {
       // throw new PendingException();
    }

    /**
     * @When browse for the image
     */
    public function browseForTheImage()
    {
        //throw new PendingException();
    }

    /**
     * @When select the one needed
     */
    public function selectTheOneNeeded()
    {
       // throw new PendingException();
    }

    /**
     * @Then you will see the uploaded image thumbnail in that area.
     */
    public function youWillSeeTheUploadedImageThumbnailInThatArea()
    {
       // throw new PendingException();
    }

    /**
     * @When the admin clicks the approve event button
     */
    public function theAdminClicksTheApproveEventButton()
    {
       // throw new PendingException();
    }

    /**
     * @Then the host would receive an email about confirmation of that event.
     */
    public function theHostWouldReceiveAnEmailAboutConfirmationOfThatEvent()
    {
        //throw new PendingException();
    }
    

// Scenario - EventPermissions.feature

    /**
     * @Given the following groups:
     */
    public function theFollowingGroups(TableNode $table)
    {
       // throw new PendingException();
    }

    /**
     * @Given the following hosts:
     */
    public function theFollowingHosts(TableNode $table)
    {
       // throw new PendingException();
    }

    /**
     * @Given Fry has created the following event:
     */
    public function fryHasCreatedTheFollowingEvent(TableNode $table)
    {
       // throw new PendingException();
    }

    /**
     * @When Leyla tries to edit the event :arg1
     */
    public function leylaTriesToEditTheEvent($arg1)
    {
        //throw new PendingException();
        $this->visit('/party/edit');
        $this->fillField('Name', $arg1);
    }

    /**
     * @Then she is able to do so
     */
    public function sheIsAbleToDoSo()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/edit');
    }


// Scenario - AddAnEvent.feature

    /**
     * @When a host clicks on event page and fills the data as follows
     */
    public function aHostClicksOnEventPageAndFillsTheDataAsFollows(TableNode $table)
    {
        //throw new PendingException();
         $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @Then he lands on events page and can see all the events in that page.
     */
    public function heLandsOnEventsPageAndCanSeeAllTheEventsInThatPage()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party');
    }

    /**
     * @When a host enters all the data needed to create an event
     */
    public function aHostEntersAllTheDataNeededToCreateAnEvent()
    {
       // throw new PendingException();
        $this->visit('/party/create');
        $this->fillField('Name of event', 'sdfgdfg');
        $this->isSelected('Event group', 'fgdfg');
        $this->fillField('Description', 'dfg');
        $this->fillField('Date of event', '23/4/2018');
        $this->fillField('Start/end time', 'fg');
        $this->fillField('Venu address', '22, sefsdfgdfg');
    }

    /**
     * @When clicks on save button
     */
    public function clicksOnSaveButton()
    {
       // throw new PendingException();
        $this->pressButton('Create event');
    }

    /**
     * @Then a success message should appear on the same page.
     */
    public function aSuccessMessageShouldAppearOnTheSamePage()
    {
       // throw new PendingException();
    }

    /**
     * @When the user clicks on save event button
     */
    public function theUserClicksOnSaveEventButton()
    {
        //throw new PendingException();
    }

    /**
     * @Then the admin would receive an notification email about event creation for moderation.
     */
    public function theAdminWouldReceiveAnNotificationEmailAboutEventCreationForModeration()
    {
       // throw new PendingException();
    }

    /**
     * @When user selects multiple images and click on upload button
     */
    public function userSelectsMultipleImagesAndClickOnUploadButton()
    {
       // throw new PendingException();
    }

    /**
     * @Then all the images should be uploaded with view of their thumbnails.
     */
    public function allTheImagesShouldBeUploadedWithViewOfTheirThumbnails()
    {
        //throw new PendingException();
    }

    /**
     * @Given logged in as a restarter
     */
    public function loggedInAsARestarter()
    {
       // throw new PendingException();
    }

    /**
     * @When the user is on the list of events page
     */
    public function theUserIsOnTheListOfEventsPage()
    {
       // throw new PendingException();
    }

    /**
     * @Then there should be no create event button.
     */
    public function thereShouldBeNoCreateEventButton()
    {
        //throw new PendingException();
    }


// Scenario - ViewAllEvents.feature

    /**
     * @When a host clicks on event page
     */
    public function aHostClicksOnEventPage()
    {
        //throw new PendingException();
        $this->visit('/party');
    }

    /**
     * @When a host wants to create a new event, should click on create new event button
     */
    public function aHostWantsToCreateANewEventShouldClickOnCreateNewEventButton()
    {
        //throw new PendingException();
        $this->visit('/party');
        $this->pressButton('Create new event');
    }

    /**
     * @Then add an event page opens.
     */
    public function addAnEventPageOpens()
    {
        //throw new PendingException();
        $this->assertPageAddress('/party/create');
    }

    /**
     * @When a host wants  to access\/check the group details
     */
    public function aHostWantsToAccessCheckTheGroupDetails()
    {
        //throw new PendingException();
        $this->visit('/party/create');
    }

    /**
     * @When clicks on the  group name link
     */
    public function clicksOnTheGroupNameLink()
    {
        //throw new PendingException();
        $this->visit('/party/create');
        $this->clickLink('Restart HQ');
    }

    /**
     * @Then host lands on that particular group page.
     */
    public function hostLandsOnThatParticularGroupPage()
    {
        //throw new PendingException();
        $this->assertPageAddress('/group/view');
    }

    /**
     * @When a host\/admin wants to enter device data into a group, click on add a device link
     */
    public function aHostAdminWantsToEnterDeviceDataIntoAGroupClickOnAddADeviceLink()
    {
        //throw new PendingException();
    }

    /**
     * @When a restarter who attended the event can only edit the device data, click on edit link
     */
    public function aRestarterWhoAttendedTheEventCanOnlyEditTheDeviceDataClickOnEditLink()
    {
       //throw new PendingException();
    }

    /**
     * @Then lands on the patrticular group page and enter\/edit the data.
     */
    public function landsOnThePatrticularGroupPageAndEnterEditTheData()
    {
       // throw new PendingException();
    }

    /**
     * @When a host wants to respond to the invite, click on the link RSVP
     */
    public function aHostWantsToRespondToTheInviteClickOnTheLinkRsvp()
    {
        //throw new PendingException();
    }

    /**
     * @Then lands on that patrticular page.
     */
    public function landsOnThatPatrticularPage()
    {
        //throw new PendingException();
    }


// Scenario - AddAGroup.feature

    /**
     * @When a host clicks on add a group page and fills the data as follows
     */
    public function aHostClicksOnAddAGroupPageAndFillsTheDataAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $this->visit('/group/create');

        foreach ($table as $row) {
             $this->fillField('Name of group', $row[]);
             $this->fillField('Your website', $row[]);
             $this->fillField('Tell us about your group', $row[]);
             $this->fillField('Group location', $row[]);
             $this->fillField('Group image', $row[]);
        }
    }

    /**
     * @When clicks on create group button to create a new group
     */
    public function clicksOnCreateGroupButtonToCreateANewGroup()
    {
        //throw new PendingException();
        $this->visit('/group/create');
        $this->pressButton('Create group');
    }

    /**
     * @Then he lands on group page with the newly created group in the list of gropus in that page.
     */
    public function heLandsOnGroupPageWithTheNewlyCreatedGroupInTheListOfGropusInThatPage()
    {
       //throw new PendingException();
        //$this->assertPageAddress('/group/create');
    }

    /**
     * @When a host clicks on group location, types the address
     */
    public function aHostClicksOnGroupLocationTypesTheAddress()
    {
        //throw new PendingException();
    }


// Scenario - AllMembersofGroup.feature

    /**
     * @When a user clicks on Join group from the group page
     */
    public function aUserClicksOnJoinGroupFromTheGroupPage()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        $this->clickLink('Join group');
    }

    /**
     * @Then a pop up appears with all the list of restarters with their skills
     */
    public function aPopUpAppearsWithAllTheListOfRestartersWithTheirSkills()
    {
        //throw new PendingException();
    }

    /**
     * @Then can click on join group button.
     */
    public function canClickOnJoinGroupButton()
    {
        //throw new PendingException();
    }


// Scenario - BecomeAHost.feature

    /**
     * @When a restarter clicks on create new group
     */
    public function aRestarterClicksOnCreateNewGroup()
    {
       // throw new PendingException();
        $this->visit('/group');
        $this->pressButton('Create new group');
    }

    /**
     * @Then a pop up appears with message and a button to get started.
     */
    public function aPopUpAppearsWithMessageAndAButtonToGetStarted()
    {
       // throw new PendingException();
        $this->assertPageAddress('group/create');
    }

    /**
     * @When a restarter does not want to create a group and wants to go back to all groups page
     */
    public function aRestarterDoesNotWantToCreateAGroupAndWantsToGoBackToAllGroupsPage()
    {
        //throw new PendingException();
        $this->visit('/group/create');
    }

    /**
     * @Then he should click on cancel to go back.
     */
    public function heShouldClickOnCancelToGoBack()
    {
        //throw new PendingException();
    }


// Scenario - EditGroup.feature
    
    /**
     * @When a host clicks on edit group page and edits the data as follows
     */
    public function aHostClicksOnEditGroupPageAndEditsTheDataAsFollows(TableNode $table)
    {
       //throw new PendingException();
    }

    /**
     * @When clicks on approve group button to save the changes
     */
    public function clicksOnApproveGroupButtonToSaveTheChanges()
    {
        //throw new PendingException();
        $this->visit('group/edit');
        $this->pressButton('Approve group');
    }

    /**
     * @Then he lands on group page with the edited group in the list of gropus in that page.
     */
    public function heLandsOnGroupPageWithTheEditedGroupInTheListOfGropusInThatPage()
    {
       // throw new PendingException();
        $this->assertPageAddress('/group');
    }

    /**
     * @When an admin clicks on add new tag link beside group tags and edits the data as follows
     */
    public function anAdminClicksOnAddNewTagLinkBesideGroupTagsAndEditsTheDataAsFollows(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @Then the edited tag appers in the field with cancel option, if needed we can delete the tag using cancel option.
     */
    public function theEditedTagAppersInTheFieldWithCancelOptionIfNeededWeCanDeleteTheTagUsingCancelOption()
    {
        //throw new PendingException();
    }


 // Scenario - EventsFilter.feature

    /**
     * @Given the following account have been created as an admin
     */
    public function theFollowingAccountHaveBeenCreatedAsAnAdmin(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When an admin wants to search for an event, fill the fields as follows
     */
    public function anAdminWantsToSearchForAnEventFillTheFieldsAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $this->visit('/search');
        // $this->fillField('By group', 'dfg');
        // $this->fillField('By event', 'test');
        // $this->fillField('From date', '05/06/2017');
        // $this->fillField('To date', '05/09/2018');
        // $this->fillField('Group tag', 'tagtest');
    }

    /**
     * @When its not mandatory to fill all the details, they are optional
     */
    public function itsNotMandatoryToFillAllTheDetailsTheyAreOptional()
    {
        //throw new PendingException();
         $this->visit('/search');
    }

    /**
     * @When click on filter results button
     */
    public function clickOnFilterResultsButton()
    {
       // throw new PendingException();
         $this->visit('/search');
         //$this->pressButton('Filter results');
    }

    /**
     * @Then he can view the filtered event results year wise in descending order along with other information.
     */
    public function heCanViewTheFilteredEventResultsYearWiseInDescendingOrderAlongWithOtherInformation()
    {
       // throw new PendingException();
        // $this->assertPageAddress('/search');
         $this->visit('/search');
         $this->assertPageContainsText('Key stats');
    }


// Scenario - GroupDescription.feature

    /**
     * @When a restarter wants to know about a group and clicks on read more link
     */
    public function aRestarterWantsToKnowAboutAGroupAndClicksOnReadMoreLink()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        //$this->clickLink('Read more');
    }

    /**
     * @Then a pop screen appears with full description of the group.
     */
    public function aPopScreenAppearsWithFullDescriptionOfTheGroup()
    {
        //throw new PendingException();
        //$this->assertPageAddress('/group/view');
         $this->visit('/group/view');
    }

    /**
     * @When a restarter wants to close the pop up screen and go back to that group page
     */
    public function aRestarterWantsToCloseThePopUpScreenAndGoBackToThatGroupPage()
    {
        //throw new PendingException();
        $this->visit('/group/view');
    }

    /**
     * @Then he can click on cancel, will land on group page.
     */
    public function heCanClickOnCancelWillLandOnGroupPage()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        //$this->assertPageAddress('/group/view');
        //$this->pressButton('button');
    }


// Scenario - InviteUsertoGroup.feature

    /**
     * @When a user clicks on Invite to group from the group page
     */
    public function aUserClicksOnInviteToGroupFromTheGroupPage()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        //$this->clickLink('Invite to group');
    }

    /**
     * @Then a pop up appears, where email address and message should be entered as follows
     */
    public function aPopUpAppearsWhereEmailAddressAndMessageShouldBeEnteredAsFollows(TableNode $table)
    {
        //throw new PendingException();
        //$this->assertPageAddress('/group/view');
        $this->visit('/group/view');
        // $this->fillField('Email addresses:', 'dsf@gfg.com');
        // $this->fillField('Invitation message', 'hi!');
    }

    /**
     * @Then can click on send invite button.
     */
    public function canClickOnSendInviteButton()
    {
       // throw new PendingException();
        //$this->assertPageAddress('/group/view');
        $this->visit('/group/view');
        $this->pressButton('Send Invites');
    }

    /**
     * @When the user clicks the send invite to group button
     */
    public function theUserClicksTheSendInviteToGroupButton()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        //$this->clickLink('Invite to group');
    }

    /**
     * @Then the volunteer that the user has sent sent invite to group would receive an email about information of that group.
     */
    public function theVolunteerThatTheUserHasSentSentInviteToGroupWouldReceiveAnEmailAboutInformationOfThatGroup()
    {
        //throw new PendingException();
    }


// Scenario - StatsEmbed.feature

    /**
     * @When a user wants to share their group stats to other places, click on Group stats embed button
     */
    public function aUserWantsToShareTheirGroupStatsToOtherPlacesClickOnGroupStatsEmbedButton()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        //$this->pressButton('Share group stats');
    }

    /**
     * @When a pop up appreas with iframe as headline stats and CO2 equivalence visualisation
     */
    public function aPopUpAppreasWithIframeAsHeadlineStatsAndCoEquivalenceVisualisation()
    {
       // throw new PendingException();
        $this->visit('/group/view');
        //$this->assertPageContainsText('Share your stats');
    }

    /**
     * @When preview widget link is useful for how the iframe looks visulally on screen
     */
    public function previewWidgetLinkIsUsefulForHowTheIframeLooksVisulallyOnScreen()
    {
       // throw new PendingException();
    }

    /**
     * @Then the user will be back on group page.
     */
    public function theUserWillBeBackOnGroupPage()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        //$this->assertPageAddress('/group/view');
    }


// Scenario - ViewAllGroups(admin).feature

    /**
     * @When an admin clicks on all groups link from the admin drop down
     */
    public function anAdminClicksOnAllGroupsLinkFromTheAdminDropDown()
    {
       // throw new PendingException();
        $this->visit('/group');
       // $this->clickLink('See all groups');
    }

    /**
     * @Then he lands on all groups page and can see all the groups
     */
    public function heLandsOnAllGroupsPageAndCanSeeAllTheGroups()
    {
        //throw new PendingException();
        //$this->assertPageAddress('/group/all');
        $this->visit('/group/all');
        //$this->assertPageContainsText('All restart groups');
    }

    /**
     * @Then can even search for group.
     */
    public function canEvenSearchForGroup()
    {
        //throw new PendingException();
        //$this->assertPageAddress('/group/all');
        $this->visit('/group/all');
        //$this->assertPageContainsText('By details');
    }

    /**
     * @When an admin wants to search group, should enter the fields provided in the By details category as follows
     */
    public function anAdminWantsToSearchGroupShouldEnterTheFieldsProvidedInTheByDetailsCategoryAsFollows(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @When the fields here are optional, so can search by only country or only name etc.,
     */
    public function theFieldsHereAreOptionalSoCanSearchByOnlyCountryOrOnlyNameEtc()
    {
        //throw new PendingException();
    }

    /**
     * @Then the admin can view the filtered group.
     */
    public function theAdminCanViewTheFilteredGroup()
    {
        //throw new PendingException();
        $this->visit('/group/all');
       // $this->assertPageAddress('/group/all');
    }

    /**
     * @When a host wants to create a new group, should click on create new group button
     */
    public function aHostWantsToCreateANewGroupShouldClickOnCreateNewGroupButton()
    {
        //throw new PendingException();
        $this->visit('/group/all');
       // $this->pressButton('Create new group');
    }

    /**
     * @Then add an group page opens.
     */
    public function addAnGroupPageOpens()
    {
        //throw new PendingException();
        //$this->assertPageAddress('/group/create');
        $this->visit('/group/all');

    }

    /**
     * @When a host wants to access\/check the group details
     */
    public function aHostWantsToAccessCheckTheGroupDetails2()
    {
        //throw new PendingException();
    }

    /**
     * @When clicks on the group name link
     */
    public function clicksOnTheGroupNameLink2()
    {
        //throw new PendingException();
    }

    /**
     * @When a host wants to check who are the hosts and restarters
     */
    public function aHostWantsToCheckWhoAreTheHostsAndRestarters()
    {
        //throw new PendingException();
    }

    /**
     * @When clicks on the number link under their respective category
     */
    public function clicksOnTheNumberLinkUnderTheirRespectiveCategory()
    {
        //throw new PendingException();
    }

    /**
     * @Then host can view the details on a pop up screen.
     */
    public function hostCanViewTheDetailsOnAPopUpScreen()
    {
        //throw new PendingException();
    }


// Scenario - ViewAllGroups.feature 

    /**
     * @When a host clicks on see all groups link
     */
    public function aHostClicksOnSeeAllGroupsLink()
    {
       // throw new PendingException();
        $this->visit('/group');
        //$this->clickLink('See all groups');
    }

    /**
     * @Then he lands on all groups page and can see all the groups in that page.
     */
    public function heLandsOnAllGroupsPageAndCanSeeAllTheGroupsInThatPage()
    {
        //throw new PendingException();
        //$this->assertPageAddress('/group/all');
        $this->visit('/group/all');
    }


// Scenario - ViewGroup_admin.feature

    /**
     * @When an admin wants to know the information about a group
     */
    public function anAdminWantsToKnowTheInformationAboutAGroup()
    {
        //throw new PendingException();
        $this->visit('/group');
    }

    /**
     * @Then he can view on the particular group page
     */
    public function heCanViewOnTheParticularGroupPage()
    {
        //throw new PendingException();
       // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
    }

    /**
     * @Then can find all the info like key stats, device breakdown, environmental impact, upcoming evetns and recently completed events.
     */
    public function canFindAllTheInfoLikeKeyStatsDeviceBreakdownEnvironmentalImpactUpcomingEvetnsAndRecentlyCompletedEvents()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
        //$this->assertPageContainsText('Key stats');
    }

    /**
     * @When an admin wants to go to other group, he can click on Group name dropdown where other group names are present
     */
    public function anAdminWantsToGoToOtherGroupHeCanClickOnGroupNameDropdownWhereOtherGroupNamesArePresent()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
        //DROPDOWN??
    }

    /**
     * @Then he can easily navigate to other groups.
     */
    public function heCanEasilyNavigateToOtherGroups()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
    }

    /**
     * @When an admin wants to know about a group
     */
    public function anAdminWantsToKnowAboutAGroup()
    {
        //throw new PendingException();
        $this->visit('/group/view');
    }

    /**
     * @Then he can view under about the group section
     */
    public function heCanViewUnderAboutTheGroupSection()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
       // $this->assertPageContainsText('About the group');
    }

    /**
     * @Then can even click on read more for more info about the group.
     */
    public function canEvenClickOnReadMoreForMoreInfoAboutTheGroup()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
        //$this->clickLink('Read more');
    }

    /**
     * @When an admin wants to know the volunteers who are present in that group
     */
    public function anAdminWantsToKnowTheVolunteersWhoArePresentInThatGroup()
    {
        //throw new PendingException();
          $this->visit('/group/view');
    }

    /**
     * @Then he can view under volunteers section.
     */
    public function heCanViewUnderVolunteersSection()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
        //$this->assertPageContainsText('Volunteers');
    }

    /**
     * @When an admin wants to add the volunteers in that group
     */
    public function anAdminWantsToAddTheVolunteersInThatGroup()
    {
       // throw new PendingException();
        $this->visit('/group/view');
    }

    /**
     * @Then he can click invite to group link under volunteers section.
     */
    public function heCanClickInviteToGroupLinkUnderVolunteersSection()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
        //$this->assertPageContainsText('Invite to group');
    }

    /**
     * @When an admin wants to add an event
     */
    public function anAdminWantsToAddAnEvent()
    {
       // throw new PendingException();
        $this->visit('/group/view');
    }

    /**
     * @Then he can click on add event link
     */
    public function heCanClickOnAddEventLink()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
        //$this->pressButton('Add event');
    }

    /**
     * @Then can RSVP and can also add a device by clicking on respective links.
     */
    public function canRsvpAndCanAlsoAddADeviceByClickingOnRespectiveLinks()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/party/view');
        $this->visit('/party/view');
        //$this->pressButton('RSVP');
    }

    /**
     * @When an admin wants to see all the events that completed recently
     */
    public function anAdminWantsToSeeAllTheEventsThatCompletedRecently()
    {
        //throw new PendingException();
        $this->visit('/party');
        //$this->assertPageContainsText('Past events');
    }

    /**
     * @Then he can click on see all events links
     */
    public function heCanClickOnSeeAllEventsLinks()
    {
       // throw new PendingException();
        // $this->assertPageAddress('/party');
        $this->visit('/party');
    }

    /**
     * @Then can add a device by clicking on its link.
     */
    public function canAddADeviceByClickingOnItsLink()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/party/view');
        $this->visit('/party/view');
        //$this->pressButton('Add');
    }


// Scenario - ViewGroup_host.feature

    /**
     * @When a host wants to know the information about a group
     */
    public function aHostWantsToKnowTheInformationAboutAGroup()
    {
        //throw new PendingException();
        $this->visit('/group/view');

    }

    /**
     * @Then can find all the info like group address, website, key stats, device breakdown, environmental impact, upcoming events and recently completed events.
     */
    public function canFindAllTheInfoLikeGroupAddressWebsiteKeyStatsDeviceBreakdownEnvironmentalImpactUpcomingEventsAndRecentlyCompletedEvents()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group/view');
        $this->visit('/group/view');
    }

    /**
     * @When a host wants to know about a group
     */
    public function aHostWantsToKnowAboutAGroup()
    {
        //throw new PendingException();
        $this->visit('/group/view');
    }

    /**
     * @When a host wants to know the volunteers who are present in that group
     */
    public function aHostWantsToKnowTheVolunteersWhoArePresentInThatGroup()
    {
        //throw new PendingException();
        $this->visit('/group/view');
       // $this->assertPageContainsText('See all volunteers');
    }

    /**
     * @When a host wants to add the volunteers in that group
     */
    public function aHostWantsToAddTheVolunteersInThatGroup()
    {
        //throw new PendingException();
        $this->visit('/group/view');
       // $this->clickLink('Invite volunteers to group');
    }

    /**
     * @When a host wants to add an event
     */
    public function aHostWantsToAddAnEvent()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        //$this->pressButton('Add event');
    }

    /**
     * @When a host wants to see all the events that completed recently
     */
    public function aHostWantsToSeeAllTheEventsThatCompletedRecently()
    {
        //throw new PendingException();
        $this->visit('/party');
    }


// Scenario - ViewGroup_restarter.feature

    /**
     * @When a restarter wants to know the information about a group
     */
    public function aRestarterWantsToKnowTheInformationAboutAGroup()
    {
       // throw new PendingException();
        $this->visit('/group/view');
    }

    /**
     * @When a restarter wants to know about a group
     */
    public function aRestarterWantsToKnowAboutAGroup()
    {
        //throw new PendingException();
        $this->visit('/group/view');
    }

    /**
     * @When a restarter wants to know the volunteers who are present in that group
     */
    public function aRestarterWantsToKnowTheVolunteersWhoArePresentInThatGroup()
    {
        //throw new PendingException();
        $this->visit('/group/view');
       // $this->clickLink('View all volunteers');
    }

    /**
     * @When a restarter wants to join as a volunteer in that group
     */
    public function aRestarterWantsToJoinAsAVolunteerInThatGroup()
    {
        //throw new PendingException();
        $this->visit('/group/view');
    }

    /**
     * @Then he can click on join gropu link under volunteers section.
     */
    public function heCanClickOnJoinGropuLinkUnderVolunteersSection()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        //$this->clickLink('Join group');
    }

    /**
     * @When a restarter wants to attend an event
     */
    public function aRestarterWantsToAttendAnEvent()
    {
        //throw new PendingException();
        $this->visit('/party');
    }

    /**
     * @Then he can click on RSVP link
     */
    public function heCanClickOnRsvpLink()
    {
        //throw new PendingException();
        $this->visit('/party/view');
       // $this->pressButton('RSVP');

    }

    /**
     * @Then can add a device to an event which is happening by clicking on add a device link.
     */
    public function canAddADeviceToAnEventWhichIsHappeningByClickingOnAddADeviceLink()
    {
        //throw new PendingException();
        $this->visit('/party/view');
        $this->pressButton('Add');
    }

    /**
     * @When a restarter wants to see all the events that completed recently
     */
    public function aRestarterWantsToSeeAllTheEventsThatCompletedRecently()
    {
       // throw new PendingException();
        $this->visit('/party');
    }

    /**
     * @When the restarter clicks on join group button
     */
    public function theRestarterClicksOnJoinGroupButton()
    {
        //throw new PendingException();
        $this->visit('/group/view');
        $this->clickLink('Join group');
    }

    /**
     * @Then the host would receive an notification email about that restarter joining the group.
     */
    public function theHostWouldReceiveAnNotificationEmailAboutThatRestarterJoiningTheGroup()
    {
        //throw new PendingException();
    }


// Scenario - YourGroups.feature

    /**
     * @When a host clicks on group page
     */
    public function aHostClicksOnGroupPage()
    {
        //throw new PendingException();
        $this->visit('/group/view');
    }

    /**
     * @Then he lands on group page and can see all the groups in that page
     */
    public function heLandsOnGroupPageAndCanSeeAllTheGroupsInThatPage()
    {
        //throw new PendingException();
       // $this->assertPageAddress('/group');
        $this->visit('/group');
    }

    /**
     * @Then one section is the list of groups that host is involved
     */
    public function oneSectionIsTheListOfGroupsThatHostIsInvolved()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group');
        $this->visit('/group');
       // $this->assertPageContainsText('Your groups');
    }

    /**
     * @Then other section is the list of groups that are near to the host along with a see all groups link.
     */
    public function otherSectionIsTheListOfGroupsThatAreNearToTheHostAlongWithASeeAllGroupsLink()
    {
        //throw new PendingException();
        // $this->assertPageAddress('/group');
        $this->visit('/group');
         // $this->assertPageContainsText('Groups nearest to you');
    }


// Scenario - EditDevice.feature
    
    /**
     * @When a restarter clicks on edit devices page
     */
    public function aRestarterClicksOnEditDevicesPage()
    {
        //throw new PendingException();
        $this->visit('/device/page-edit');
    }

    /**
     * @When change\/update the fields as follows
     */
    public function changeUpdateTheFieldsAsFollows(TableNode $table)
    {
       // throw new PendingException();
        $this->visit('/device/page-edit');
    }

    /**
     * @When click on save device to save the changes
     */
    public function clickOnSaveDeviceToSaveTheChanges()
    {
        //throw new PendingException();
        $this->visit('/device/page-edit');
       // $this->pressButton('Save device');
    }

    /**
     * @Then you will land on all devices page with the edited device on the list of devices.
     */
    public function youWillLandOnAllDevicesPageWithTheEditedDeviceOnTheListOfDevices()
    {
        //throw new PendingException();
        //  $this->assertPageAddress('/device');
        $this->visit('/device');
    }

    /**
     * @When a restarter wants to delete a device, click on delete device button
     */
    public function aRestarterWantsToDeleteADeviceClickOnDeleteDeviceButton()
    {
        //throw new PendingException();
        $this->visit('/device/page-edit');
       // $this->pressButton('Delete device');
    }

    /**
     * @Then you will land on all devices page and you won't be able to see the deleted device from the list of devices.
     */
    public function youWillLandOnAllDevicesPageAndYouWontBeAbleToSeeTheDeletedDeviceFromTheListOfDevices()
    {
       // throw new PendingException();
      //  $this->assertPageAddress('/device');
        $this->visit('/device');
    }


// Scenario - ViewAllDevices.feature

    /**
     * @When a restarter clicks on devices page
     */
    public function aRestarterClicksOnDevicesPage()
    {
        //throw new PendingException();
        $this->visit('/device');
    }

    /**
     * @Then he can see all the devices starting from recent ones on the top of the page.
     */
    public function heCanSeeAllTheDevicesStartingFromRecentOnesOnTheTopOfThePage()
    {
       // throw new PendingException();
       // $this->assertPageAddress('/device');
        $this->visit('/device');
    }

    /**
     * @When a restarter wants to search for the devices, he can fill the fields as he want to search as follows
     */
    public function aRestarterWantsToSearchForTheDevicesHeCanFillTheFieldsAsHeWantToSearchAsFollows(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @When should click on search all devices button
     */
    public function shouldClickOnSearchAllDevicesButton()
    {
       // throw new PendingException();
        $this->visit('/device');
        //$this->pressButton('Search all devices');
    }

    /**
     * @Then user can view the list or a particular device that searched for.
     */
    public function userCanViewTheListOrAParticularDeviceThatSearchedFor()
    {
        //throw new PendingException();
        $this->visit('/device');
    }


// Scenario - BreakdownbyCountry.feature
   
    /**
     * @When a restarter wants to see the total time volunteered country wise, click on see all results link in breakdown by country section
     */
    public function aRestarterWantsToSeeTheTotalTimeVolunteeredCountryWiseClickOnSeeAllResultsLinkInBreakdownByCountrySection()
    {
        //throw new PendingException();
        $this->visit('/reporting/time-volunteered');
       // $this->clickLink('See all results');
    }

    /**
     * @Then a pop up appears with all the country names and the time volunteered in the countries.
     */
    public function aPopUpAppearsWithAllTheCountryNamesAndTheTimeVolunteeredInTheCountries()
    {
       // throw new PendingException();
        //$this->assertPageAddress('/reporting/time-volunteered');
          $this->visit('/reporting/time-volunteered');
        //$this->assertPageContainsText('Volunteer hours grouped by volunteer country.');
    }

    /**
     * @When a restarter wants to go back to time volunteered page, click on Cancel
     */
    public function aRestarterWantsToGoBackToTimeVolunteeredPageClickOnCancel()
    {
        //throw new PendingException();
          $this->visit('/reporting/time-volunteered');
         // $this->pressButton('button');
    }

    /**
     * @Then the restarter will go back to time volunteered page.
     */
    public function theRestarterWillGoBackToTimeVolunteeredPage()
    {
        //throw new PendingException();
        //$this->assertPageAddress('/reporting/time-volunteered');
          $this->visit('/reporting/time-volunteered');
    }


// Scenario - Impact Analysis.feature

    /**
     * @Given the following events:
     */
    public function theFollowingEvents(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @Given the following devices logged for the :arg1 event:
     */
    public function theFollowingDevicesLoggedForTheEvent($arg1, TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @When viewing the stats for the :arg1 event
     */
    public function viewingTheStatsForTheEvent($arg1)
    {
        //throw new PendingException();

    }

    /**
     * @Then the stats should be:
     */
    public function theStatsShouldBe(TableNode $table)
    {
       // throw new PendingException();
    }

    /**
     * @When viewing the stats for the :arg1 group
     */
    public function viewingTheStatsForTheGroup($arg1)
    {
       // throw new PendingException();
    }

    /**
     * @Then the stats should be:
     */
    // public function theStatsShouldBe()
    // {
    //    // throw new PendingException();
    // }


// Scenario - Timevolunteered.feature
    
    /**
     * @When a restarter wants to see the total time volunteered
     */
    public function aRestarterWantsToSeeTheTotalTimeVolunteered()
    {
        //throw new PendingException();
        $this->visit('/reporting/time-volunteered');
    }

    /**
     * @Then he can see all the information about volunteered time on time volunteered page.
     */
    public function heCanSeeAllTheInformationAboutVolunteeredTimeOnTimeVolunteeredPage()
    {
       // throw new PendingException();
         //$this->assertPageAddress('/reporting/time-volunteered');
         $this->visit('/reporting/time-volunteered');
    }

    /**
     * @When a restarter wants to search for a particular period of time volunteered, he can fill the fields as he want to search as follows
     */
    public function aRestarterWantsToSearchForAParticularPeriodOfTimeVolunteeredHeCanFillTheFieldsAsHeWantToSearchAsFollows(TableNode $table)
    {
       // throw new PendingException();
    }

    /**
     * @When should click on search all time volunteered
     */
    public function shouldClickOnSearchAllTimeVolunteered()
    {
        //throw new PendingException();
         $this->visit('/reporting/time-volunteered');
         //$this->pressButton('Search time volunteered');
    }

    /**
     * @Then user can view the list of time volunteered.
     */
    public function userCanViewTheListOfTimeVolunteered()
    {
        //throw new PendingException();
         //$this->assertPageAddress('/reporting/time-volunteered');
         $this->visit('/reporting/time-volunteered');
    }


// Scenario - AddBrand.feature
    

    /**
     * @When a new brand name is added, to do so fill the field as follows and Click on Create new brand button to save the changes
     */
    public function aNewBrandNameIsAddedToDoSoFillTheFieldAsFollowsAndClickOnCreateNewBrandButtonToSaveTheChanges(TableNode $table)
    {
       // throw new PendingException();
    }

    /**
     * @Then you will land on All brands page with newly added brand in the list and also with a message that your brand is added.
     */
    public function youWillLandOnAllBrandsPageWithNewlyAddedBrandInTheListAndAlsoWithAMessageThatYourBrandIsAdded()
    {
       // throw new PendingException();
        //$this->assertPageAddress('/brands');
       $this->visit('/brands');
        //$this->assertPageContainsText('');
    }



// Scenario - EditBrand.feature

    /**
     * @When a brand name is edited, should edit the field as follows and click on save brand button to save the changes
     */
    public function aBrandNameIsEditedShouldEditTheFieldAsFollowsAndClickOnSaveBrandButtonToSaveTheChanges(TableNode $table)
    {
        //throw new PendingException();
        $this->visit('/brands/edit');
    }

    /**
     * @Then she will land on brands name page with the edited brand name in the list, pop-up message saying your changes have beeen saved.
     */
    public function sheWillLandOnBrandsNamePageWithTheEditedBrandNameInTheListPopUpMessageSayingYourChangesHaveBeeenSaved()
    {
        //throw new PendingException();
        $this->visit('/brands');
        //$this->assertPageAddress('/brands');
        //$this->assertPageContainsText('');
    }



// Scenario - ViewAllBrands.feature

    /**
     * @Given the following account have been created as an admin\/user
     */
    public function theFollowingAccountHaveBeenCreatedAsAnAdminUser(TableNode $table)
    {
        //throw new PendingException();
         $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When an admin navigate to Brand page
     */
    public function anAdminNavigateToBrandPage()
    {
        //throw new PendingException();
         $this->visit('/brands');
    }

    /**
     * @Then she can view all the brand names in that page.
     */
    public function sheCanViewAllTheBrandNamesInThatPage()
    {
        //throw new PendingException();
         $this->visit('/brands');
         //$this->assertPageAddress('/brands');
         //$this->assertPageContainsText('Brand name');
    }

    /**
     * @When an admin wants to create a new brand
     */
    public function anAdminWantsToCreateANewBrand()
    {
        //throw new PendingException();
        $this->visit('/brands');
    }

    /**
     * @Then he\/she should click on create new brand button.
     */
    public function heSheShouldClickOnCreateNewBrandButton()
    {
        //throw new PendingException();
        $this->visit('/brands');
        //$this->assertPageAddress('/brands');
        //$this->pressButton('Create new brand');
    }



// Scenario - EditCategory.feature

    /**
     * @Given the following account have been created as a user or an admin
     */
    public function theFollowingAccountHaveBeenCreatedAsAUserOrAnAdmin(TableNode $table)
    {
        //throw new PendingException();
        $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When the fields are changed\/updated in edit category section as follows
     */
    public function theFieldsAreChangedUpdatedInEditCategorySectionAsFollows(TableNode $table)
    {
       // throw new PendingException();
        $this->visit('/category/edit');
    }

    /**
     * @When click on save category
     */
    public function clickOnSaveCategory()
    {
        //throw new PendingException();
        $this->visit('/category/edit');
       // $this->pressButton('Save category');
    }

    /**
     * @Then she will land on All categories page with the edited category in the list of categories.
     */
    public function sheWillLandOnAllCategoriesPageWithTheEditedCategoryInTheListOfCategories()
    {
        //throw new PendingException();
        $this->visit('/category');
        //$this->assertPageAddress('/category');
    }



// Scenario -  ViewAllCategories.feature
    
    /**
     * @When an admin view all the categories
     */
    public function anAdminViewAllTheCategories()
    {
        //throw new PendingException();
        $this->visit('/category');
    }

    /**
     * @Then he\/she should navigate to categories page.
     */ 
    public function heSheShouldNavigateToCategoriesPage()
    {
        //throw new PendingException();
        $this->visit('/category');
        //$this->assertPageAddress('/category');
    }



// Scenario - AddNewGroupTag.feature

    /**
     * @When the fields are added as follows
     */
    public function theFieldsAreAddedAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $this->visit('/tags');
    }

    /**
     * @When should click on Create new tag button to save the changes
     */
    public function shouldClickOnCreateNewTagButtonToSaveTheChanges()
    {
       // throw new PendingException();
        $this->visit('/tags');
       // $this->pressButton('Create new tag');
    }

    /**
     * @Then she should land on group tag page with the recently added group tag in list of tags.
     */
    public function sheShouldLandOnGroupTagPageWithTheRecentlyAddedGroupTagInListOfTags()
    {
        //throw new PendingException();
        $this->visit('/tags');
        //$this->assertPageAddress('/tags');
    }


// Scenario - EditGroupTag.feature

    /**
     * @When the fields are editted as follows
     */
    public function theFieldsAreEdittedAsFollows(TableNode $table)
    {
        //throw new PendingException();
        $this->visit('/tags/edit');
    }

    /**
     * @When should click on save tag button to save the changes
     */
    public function shouldClickOnSaveTagButtonToSaveTheChanges()
    {
        //throw new PendingException();
        $this->visit('/tags/edit');
        //$this->pressButton('Save tag');
    }

    /**
     * @Then she should land on group tags page with the edited tag in the list of tags.
     */
    public function sheShouldLandOnGroupTagsPageWithTheEditedTagInTheListOfTags()
    {
        //throw new PendingException();
        $this->visit('/tags/edit');
        //$this->assertPageAddress('/tags/edit');
        //$this->assertPageContainsText('Group Tag successfully updated!');
    }

    /**
     * @When an admin wants to delete a group tag
     */
    public function anAdminWantsToDeleteAGroupTag()
    {
        //throw new PendingException();
        $this->visit('/tags/edit');
    }

    /**
     * @When click on delete tag button to delete the group tag
     */
    public function clickOnDeleteTagButtonToDeleteTheGroupTag()
    {
        //throw new PendingException();
        $this->visit('/tags/edit');
       // $this->pressButton('Delete tag');
    }

    /**
     * @Then she should land on group tags pages with no trace of the deleted tag in the list.
     */
    public function sheShouldLandOnGroupTagsPagesWithNoTraceOfTheDeletedTagInTheList()
    {
        //throw new PendingException();
        $this->visit('/tags');
        //$this->assertPageAddress('/tags');
        //$this->assertPageContainsText('Group Tag successfully deleted!');
    }



// Scenario - ViewAllGroupTags.feature
    
    /**
     * @When an admin wants to see all the group tags at one place
     */
    public function anAdminWantsToSeeAllTheGroupTagsAtOnePlace()
    {
       // throw new PendingException();
        $this->visit('/tags');
    }

    /**
     * @Then she should navigate to Group Tags page.
     */
    public function sheShouldNavigateToGroupTagsPage()
    {
        //throw new PendingException();
        $this->visit('/tags');
        //$this->assertPageAddress('/tags');
    }

    /**
     * @When an admin wanted to create a new group tag
     */
    public function anAdminWantedToCreateANewGroupTag()
    {
        //throw new PendingException();
        $this->visit('/tags');
    }

    /**
     * @Then he\/she should click on create new tag button.
     */
    public function heSheShouldClickOnCreateNewTagButton()
    {
        //throw new PendingException();
        $this->visit('/tags');
        //$this->assertPageAddress('/tags');
        //$this->pressButton('Create new tag');
    }


// Scenario - EditRole.feature

    /**
     * @When the user permission(s) checked
     */
    public function theUserPermissionSChecked()
    {
        //throw new PendingException();
        $this->visit('/role/edit');        
    }

    /**
     * @When the user will have those permissions to do and click on save role to save the changes
     */
    public function theUserWillHaveThosePermissionsToDoAndClickOnSaveRoleToSaveTheChanges()
    {
        //throw new PendingException();
        $this->visit('/role/edit');
        $this->pressButton('Save role');
    }

    /**
     * @Then she should land on All users page with the edited user in the list of users.
     */
    public function sheShouldLandOnAllUsersPageWithTheEditedUserInTheListOfUsers()
    {
        //throw new PendingException();
        $this->visit('/role');
        //$this->assertPageAddress('/role');
    }


// Scenario - ViewAllRoles.feature

    /**
     * @When an admin wants to view the permissions of the Roles
     */
    public function anAdminWantsToViewThePermissionsOfTheRoles()
    {
        //throw new PendingException();
    }

    /**
     * @Then navigate to roles page
     */
    public function navigateToRolesPage()
    {
        //throw new PendingException();
        //$this->assertPageAddress('/role');
        $this->visit('/role');
    }



// Scenario - AddNewSkill.feature

    /**
     * @When a admin needs new skill to their profile, they should fill the fields as follows
     */
    public function aAdminNeedsNewSkillToTheirProfileTheyShouldFillTheFieldsAsFollows(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @When click on Create new skill button to save the changes
     */
    public function clickOnCreateNewSkillButtonToSaveTheChanges()
    {
        //throw new PendingException();
        $this->visit('/skills');
        //$this->pressButton('Create new skill');
    }

    /**
     * @Then she should land on all skills page with the new skill added in the list of skills, with a message saying new skill have been added.
     */
    public function sheShouldLandOnAllSkillsPageWithTheNewSkillAddedInTheListOfSkillsWithAMessageSayingNewSkillHaveBeenAdded()
    {
        //throw new PendingException();
        $this->assertPageContainsText('Skill successfully updated!');
        $this->visit('/skills/edit');
        //$this->assertPageAddress('/skills/edit');
    }



// Scenario - EditSkill.feature
    
    /**
     * @When an admin edit a skill which is in their profile, they should edit the fields as follows
     */
    public function anAdminEditASkillWhichIsInTheirProfileTheyShouldEditTheFieldsAsFollows(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @When click on save skill button to save the changes
     */
    public function clickOnSaveSkillButtonToSaveTheChanges()
    {
        //throw new PendingException();
        $this->visit('/skills/edit');
        $this->pressButton('Save skill');
    }

    /**
     * @Then she will land on all skills page with the edited skill in the list of skills, with a message saying your changes have been saved.
     */
    public function sheWillLandOnAllSkillsPageWithTheEditedSkillInTheListOfSkillsWithAMessageSayingYourChangesHaveBeenSaved()
    {
        //throw new PendingException();
        $this->visit('/skills/edit');
       // $this->assertPageAddress('/skills/edit');
        //$this->assertPageContainsText('Skill successfully updated!');

    }

    /**
     * @When an admin wants to delete a skill which is in their profile
     */
    public function anAdminWantsToDeleteASkillWhichIsInTheirProfile()
    {
       // throw new PendingException();
        $this->visit('/skills/edit');
    }

    /**
     * @When click on delete skill button to delete the skill
     */
    public function clickOnDeleteSkillButtonToDeleteTheSkill()
    {
        //throw new PendingException();
        $this->visit('/skills/edit');
        //$this->pressButton('Delete skill');
    }

    /**
     * @Then she will land on all skills page where the deleted skill will no longer be there in the list of skills, with a message saying your skill have been deleted.
     */
    public function sheWillLandOnAllSkillsPageWhereTheDeletedSkillWillNoLongerBeThereInTheListOfSkillsWithAMessageSayingYourSkillHaveBeenDeleted()
    {
       // throw new PendingException();
        $this->visit('/skills');
        //$this->assertPageAddress('/skills');
    }


// Scenario - ViewAllSkills.feature

    /**
     * @Given the following account have been created as a admin
     */
    public function theFollowingAccountHaveBeenCreatedAsAAdmin(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @When an admin wants to know the description of skills
     */
    public function anAdminWantsToKnowTheDescriptionOfSkills()
    {
        //throw new PendingException();
        $this->visit('/skills');
    }

    /**
     * @Then they can navigate to the Skills page.
     */
    public function theyCanNavigateToTheSkillsPage()
    {
       // throw new PendingException();
         $this->visit('/skills');
        //$this->assertPageAddress('/skills');
    }

    /**
     * @When an admin wants to add a skill to their profile
     */
    public function anAdminWantsToAddASkillToTheirProfile()
    {
        //throw new PendingException();
         $this->visit('/profile/edit');
    }

    /**
     * @Then click on create new skill button and follow the steps.
     */
    public function clickOnCreateNewSkillButtonAndFollowTheSteps()
    {
        //throw new PendingException();
    }


// Scenario - AddNewUser.feature

    /**
     * @Given an Admin user is on the All Users page
     */
    public function anAdminUserIsOnTheAllUsersPage()
    {
        //throw new PendingException();
         $admin = factory(User::class)->states('Administrator')->create();
    }

    /**
     * @When she clicks the New User button
     */
    public function sheClicksTheNewUserButton()
    {
        //throw new PendingException();
        $this->visit('/user/all');
        //$this->pressButton('Create new user');
    }

    /**
     * @Then she is shown the dialog for creating the new user
     */
    public function sheIsShownTheDialogForCreatingTheNewUser()
    {
        //throw new PendingException();
        $this->visit('/user/all');
        //$this->assertPageAddress('/user/all');
    }

    /**
     * @Given an Admin is creating a new user
     */
    public function anAdminIsCreatingANewUser()
    {
        //throw new PendingException();
    }

    /**
     * @When she enters the new user's details in the fields provided as follows:
     */
    public function sheEntersTheNewUsersDetailsInTheFieldsProvidedAsFollows(TableNode $table)
    {
       // throw new PendingException();
    }

    /**
     * @When she clicks :arg1
     */
    public function sheClicks($arg1)
    {
       // throw new PendingException();
        $this->visit('/user/all');
        //$this->pressButton($arg1);
    }

    /**
     * @Then she lands on the All Users page with the newly added user in the list of users
     */
    public function sheLandsOnTheAllUsersPageWithTheNewlyAddedUserInTheListOfUsers()
    {
        //throw new PendingException();
        $this->visit('/user/all');
       // $this->assertPageAddress('/user/all');
    }

    /**
     * @Then she is shown a message saying that new user has been added successfully
     */
    public function sheIsShownAMessageSayingThatNewUserHasBeenAddedSuccessfully()
    {
        //throw new PendingException();
        $this->assertPageContainsText('New user successfully created');
    }

    /**
     * @Then an error message should at the password field, password should be more than :arg1 characters.
     */
    public function anErrorMessageShouldAtThePasswordFieldPasswordShouldBeMoreThanCharacters($arg1)
    {
       // throw new PendingException();
         $this->visit('/user/all');
       // $this->assertPageAddress('/user/all');
    }


// Scenario - DeleteUser.feature

    /**
     * @Given an Admin is on a user's account page
     */
    public function anAdminIsOnAUsersAccountPage()
    {
        //throw new PendingException();
        $this->visit('/user/edit');
    }

    /**
     * @When she deletes the users account
     */
    public function sheDeletesTheUsersAccount()
    {
        //throw new PendingException();
        $this->visit('/user/edit');
        //$this->pressButton('Delete account');
    }

    /**
     * @Then the user's personal data is anonymised
     */
    public function theUsersPersonalDataIsAnonymised()
    {
       //throw new PendingException();
    }

    /**
     * @Then the account is marked as inactive
     */
    public function theAccountIsMarkedAsInactive()
    {
       // throw new PendingException();
    }

    /**
     * @Then the Admin is directed to the All Users page
     */
    public function theAdminIsDirectedToTheAllUsersPage()
    {
       // throw new PendingException();
        $this->visit('/user/all');
       // $this->assertPageAddress('/user/all');
    }

    /**
     * @Then the Admin is shown a message showing that this user has been successfully deleted
     */
    public function theAdminIsShownAMessageShowingThatThisUserHasBeenSuccessfullyDeleted()
    {
       // throw new PendingException();
    }



// Scenario - EditUser.feature

    /**
     * @When a user wants to change\/update any details
     */
    public function aUserWantsToChangeUpdateAnyDetails()
    {
        //throw new PendingException();
        $this->visit('/user/edit');
    }

    /**
     * @When he\/she should be able to do that by changing the details and saving them
     */
    public function heSheShouldBeAbleToDoThatByChangingTheDetailsAndSavingThem()
    {
       // throw new PendingException();
        $this->visit('/user/edit');
       // $this->pressButton('Save profile');
    }

    /**
     * @Then she should land on the Users page with the edited user in the list of users, a message saying that the changes have been saved .
     */
    public function sheShouldLandOnTheUsersPageWithTheEditedUserInTheListOfUsersAMessageSayingThatTheChangesHaveBeenSaved()
    {
        //throw new PendingException();
       //$this->assertPageContainsText('User Profile Updated!');

    }

    /**
     * @When a user enter details in User Profile section as follows and clicks on save profile
     */
    public function aUserEnterDetailsInUserProfileSectionAsFollowsAndClicksOnSaveProfile(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @When the user saves all the changes he made in that section
     */
    public function theUserSavesAllTheChangesHeMadeInThatSection()
    {
        //throw new PendingException();
        $this->visit('/user/edit');
    }

    /**
     * @Then she should land on the profile page with a message saying that the changes have been saved.
     */
    public function sheShouldLandOnTheProfilePageWithAMessageSayingThatTheChangesHaveBeenSaved()
    {
        //throw new PendingException();
    }

    /**
     * @When a user types the skills he\/she have
     */
    public function aUserTypesTheSkillsHeSheHave(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @When the user saves the changes in that section
     */
    public function theUserSavesTheChangesInThatSection()
    {
       // throw new PendingException();
    }

    /**
     * @When a user wants to change their profile picture
     */
    public function aUserWantsToChangeTheirProfilePicture()
    {
        //throw new PendingException();
    }

    /**
     * @When browse the pic and click on change photo button
     */
    public function browseThePicAndClickOnChangePhotoButton()
    {
        //throw new PendingException();
    }

    /**
     * @Then she should land on profile page with the uploaded picture in the placeholder, with a message saying the picture has been uploaded.
     */
    public function sheShouldLandOnProfilePageWithTheUploadedPictureInThePlaceholderWithAMessageSayingThePictureHasBeenUploaded()
    {
        //throw new PendingException();
    }



// Scenario - EditUser_Acc.feature

    /**
     * @When an admin changes\/updates any account details and clicks on save
     */
    public function anAdminChangesUpdatesAnyAccountDetailsAndClicksOnSave()
    {
       // throw new PendingException();
        $this->visit('/user/edit');
       // $this->pressButton('Save user');
    }

    /**
     * @Then he\/she should see an pop up message as changes have been saved.
     */
    public function heSheShouldSeeAnPopUpMessageAsChangesHaveBeenSaved()
    {
        //throw new PendingException();
    }

    /**
     * @When changes are made in the fields as follows and clicks on change password button
     */
    public function changesAreMadeInTheFieldsAsFollowsAndClicksOnChangePasswordButton(TableNode $table)
    {
        //throw new PendingException();
         $this->visit('/user/edit');
       // $this->pressButton('Change password');
    }

    /**
     * @Then a pop-up message shows saying all the changes have been saved.
     */
    public function aPopUpMessageShowsSayingAllTheChangesHaveBeenSaved()
    {
       //throw new PendingException();
         $this->visit('/user/edit');
       // $this->assertPageContainsText('Admin settings updated');
    }

    /**
     * @When the admin uses this page to change a users role and group
     */
    public function theAdminUsesThisPageToChangeAUsersRoleAndGroup()
    {
        //throw new PendingException();
         $this->visit('/user/edit');
    }

    /**
     * @Then only admin can have that privilage to do.
     */
    public function onlyAdminCanHaveThatPrivilageToDo()
    {
       // throw new PendingException();
         $this->visit('/user/edit');
    }



// Scenario - EditUser_Emailpref.feature


    /**
     * @When an admin wants to get notified by the Restart Project
     */
    public function anAdminWantsToGetNotifiedByTheRestartProject()
    {
        //throw new PendingException();
        //$this->visit('/user/edit');
    }

    /**
     * @When ticking-off the checkbox and click on save preferences button
     */
    public function tickingOffTheCheckboxAndClickOnSavePreferencesButton()
    {
        //throw new PendingException();
        $this->visit('/user/edit');
        // $this->checkOption('newsletter');
        // $this->checkOption('invities');
    }

    /**
     * @Then she should land on Email preferences page with a message saying that the changes have been saved.
     */
    public function sheShouldLandOnEmailPreferencesPageWithAMessageSayingThatTheChangesHaveBeenSaved()
    {
        //throw new PendingException();
        $this->visit('/user/edit');
       // $this->assertPageAddress('/user/edit');
       // $this->assertPageContainsText('User Preferences Updated!');
    }

    /**
     * @When a user create a email or set an email to Restart Project discussion platform
     */
    public function aUserCreateAEmailOrSetAnEmailToRestartProjectDiscussionPlatform()
    {
        //throw new PendingException();
    }

    /**
     * @Then the user receives the information to that email id
     */
    public function theUserReceivesTheInformationToThatEmailId()
    {
        //throw new PendingException();
    }



// Screnario - ViewAllUsers.feature

    /**
     * @When an admin enter details of a particular user in the feilds provided as follows
     */
    public function anAdminEnterDetailsOfAParticularUserInTheFeildsProvidedAsFollows(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @Then the admin should get the details of that particalr user.
     */
    public function theAdminShouldGetTheDetailsOfThatParticalrUser()
    {
        //throw new PendingException();
        $this->visit('/user/all');
       // $this->assertPageAddress('/user/all');
    }

    /**
     * @When an admin does not enter any field as follows
     */
    public function anAdminDoesNotEnterAnyFieldAsFollows(TableNode $table)
    {
        //throw new PendingException();
    }

    /**
     * @When clicks on search users button
     */
    public function clicksOnSearchUsersButton()
    {
      //  throw new PendingException();
        $this->visit('/user/all');
        //$this->pressButton('Search all users');
    }

    /**
     * @Then she will land on All users page without any changes.
     */
    public function sheWillLandOnAllUsersPageWithoutAnyChanges()
    {
        //throw new PendingException();
        $this->visit('/user/all');
       // $this->assertPageAddress('/user/all');
    }



// Scenario - ViewProfile.feature

    /** @BeforeScenario */
public function before(BeforeScenarioScope $scope)
{
    $this->getSession()->restart();
}

    /**
     * @Given I am logged in
     */
    public function iAmLoggedIn()
    {
        
        //throw new PendingException();
        $user = factory(User::class)->create();
        $this->visit('/login');
        $this->assertPageAddress('/login');
        //$driver = $this->getSession()->getDriver();
        $page = $this->getSession()->getPage();
        $html = $page->GetContent();
        //echo $page;
        //$this->getSession()->visit($this->locatePath('/blog'))
        //$this->visit('/login');
        //sleep(1000000);
        //dd($page);
        //$element = $page->findAll('css', 'div');
        //dd($element);

        $this->fillField('email', $user->email);
        $this->fillField('password','secret');
        $this->pressButton('Login');
    }

    /**
     * @When a user wants to see the biography and skills of a user and click on view profile
     */
    public function aUserWantsToSeeTheBiographyAndSkillsOfAUserAndClickOnViewProfile()
    {
        //throw new PendingException();
        $this->visit('/profile');
    }

    /**
     * @Then they will land on view profile page with their details.
     */
    public function theyWillLandOnViewProfilePageWithTheirDetails()
    {
        //throw new PendingException();
         //$this->visit('/profile');
        $this->assertPageAddress('/profile');

    }

    /**
     * @When user wants to change the profile, click on edit profile button
     */
    public function userWantsToChangeTheProfileClickOnEditProfileButton()
    {
       // throw new PendingException();
         $this->visit('/profile');
         $this->clickLink('Edit user');
    }

    /**
     * @Then user will land on edit profile page.
     */
    public function userWillLandOnEditProfilePage()
    {
       // throw new PendingException();
        $this->visit('/profile/edit');
        //$this->assertPageAddress('/profile/edit');
    }



 //Scenario - AdminMenu.feature

    /**
     * @When a host clicks on Discussion in the menu
     */
    public function aHostClicksOnDiscussionInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on https:\/\/talk.restarters.net\/ page.
     */
    public function theyLandOnHttpsTalkRestartersNetPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Restart Wiki in the menu
     */
    public function aHostClicksOnRestartWikiInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on https:\/\/therestartproject.org\/wiki\/Main_Page page.
     */
    public function theyLandOnHttpsTherestartprojectOrgWikiMainPagePage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on The Repair Directory in the menu
     */
    public function aHostClicksOnTheRepairDirectoryInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on https:\/\/therestartproject.org\/repairdirectory\/ page.
     */
    public function theyLandOnHttpsTherestartprojectOrgRepairdirectoryPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on The Restart Project in the menu
     */
    public function aHostClicksOnTheRestartProjectInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on https:\/\/therestartproject.org\/ page.
     */
    public function theyLandOnHttpsTherestartprojectOrgPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Help in the menu
     */
    public function aHostClicksOnHelpInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Help page.
     */
    public function theyLandOnHelpPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Welcome in the menu
     */
    public function aHostClicksOnWelcomeInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Welcome page.
     */
    public function theyLandOnWelcomePage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Brands in the menu
     */
    public function aHostClicksOnBrandsInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Brands page.
     */
    public function theyLandOnBrandsPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Skills in the menu
     */
    public function aHostClicksOnSkillsInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Skills page.
     */
    public function theyLandOnSkillsPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Group tags in the menu
     */
    public function aHostClicksOnGroupTagsInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Group tags page.
     */
    public function theyLandOnGroupTagsPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Categories in the menu
     */
    public function aHostClicksOnCategoriesInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Categories page.
     */
    public function theyLandOnCategoriesPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Users in the menu
     */
    public function aHostClicksOnUsersInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Users page.
     */
    public function theyLandOnUsersPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Roles in the menu
     */
    public function aHostClicksOnRolesInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Roles page.
     */
    public function theyLandOnRolesPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Time reporting in the menu
     */
    public function aHostClicksOnTimeReportingInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Time reporting page.
     */
    public function theyLandOnTimeReportingPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Event reporting in the menu
     */
    public function aHostClicksOnEventReportingInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Event reporting page.
     */
    public function theyLandOnEventReportingPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Your profile in the menu
     */
    public function aHostClicksOnYourProfileInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Your profile page.
     */
    public function theyLandOnYourProfilePage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Changed pasword in the menu
     */
    public function aHostClicksOnChangedPaswordInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Changed pasword page.
     */
    public function theyLandOnChangedPaswordPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Logout in the menu
     */
    public function aHostClicksOnLogoutInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Logout page.
     */
    public function theyLandOnLogoutPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Events in the menu
     */
    public function aHostClicksOnEventsInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Events page.
     */
    public function theyLandOnEventsPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Devices in the menu
     */
    public function aHostClicksOnDevicesInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Devices page.
     */
    public function theyLandOnDevicesPage()
    {
        throw new PendingException();
    }

    /**
     * @When a host clicks on Groups in the menu
     */
    public function aHostClicksOnGroupsInTheMenu()
    {
        throw new PendingException();
    }

    /**
     * @Then they land on Groups page.
     */
    public function theyLandOnGroupsPage()
    {
        throw new PendingException();
    }

    
    
}
