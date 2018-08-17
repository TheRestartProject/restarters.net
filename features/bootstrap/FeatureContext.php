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
     * @Given a user account with has been created with email :email and password :password
     */
    public function aUserAccountWithHasBeenCreatedWithEmailAndPassword($email, $password)
    {
        $user = factory(User::class)->create([
            'email' => $email,
            'password' => Hash::make($password)
        ]);
    }

    /**
     * @When a user logs in with email :arg1 and password :arg2
     */
    public function aUserLogsInWithEmailAndPassword($arg1, $arg2)
    {
        //throw new PendingException();
        $this->visit('http://127.0.0.1:8000');
        //$this->printLastResponse();
        //$this->assertResponseStatus(500);

       // $this->assertPageContainsText('Sign in');
        $this->fillField('email', $arg1);
        $this->fillField('password', $arg2);

        Honeypot::generate('my_name', 'my_time');

        $this->pressButton('Login');
    }

    /**
     * @Then the user is logged in as :arg1 with email :arg2
     */
    public function theUserIsLoggedInAsWithEmail($arg1, $arg2)
    {
        //throw new PendingException();
        $this->getSession()->wait(5000);
        $this->assertPageAddress('/dashboard');

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
     * @Then :text message is displayed to the user letting them know they have not been logged in
     */
    public function messageIsDisplayedToTheUserLettingThemKnowTheyHaveNotBeenLoggedIn($text)
    {
        //throw new PendingException();
        //$this->printLastResponse();
         $this->assertPageContainsText($text);
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
       $this->visit('/login');
    }

    /**
     * @Then the user should be presented with the onboarding text and images
     */
    public function theUserShouldBePresentedWithTheOnboardingTextAndImages()
    {
        //throw new PendingException();
        $this->assertSee('We are a global community of people who help others fix their electronics in community events. Join us!');
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
         $this->fillField('email', $arg1);
         $this->clickLink('Forgot password'); 

    }

    /**
     * @Then the user would receive an email to his registered email account, to reset password.
     */
    public function theUserWouldReceiveAnEmailToHisRegisteredEmailAccountToResetPassword()
    {
        //throw new PendingException();
        
    }
}
