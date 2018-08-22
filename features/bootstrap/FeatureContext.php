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
}
