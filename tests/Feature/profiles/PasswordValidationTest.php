<?php

namespace Tests\Feature;

use App\User;

use Carbon\Carbon;
use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;


class PasswordValidationTest extends TestCase
{
    //use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function setting_user_password_of_6_characters()
    {
        // given we have a user
        $user = factory(User::class)->create()->first();
        // and we're logged in as that user
        $this->actingAs($user);

        // when the user navigates to their profile editing page
        $response = $this->get('/profile/edit/' . $user->id);
        // and edits their password with a valid password (i.e. 6 or more characters)
        $newPassword = 'str0ngp4ssw0rd';
        // ... post data for current password, new password, and confirm password
        $existingPassword = 'secret';


        $response = $this->post('/profile/edit-password/', [
            'current-password' => $existingPassword,
            'new-password' => $newPassword,
            'new-password-repeat' => $newPassword
        ]);
          
        $newPasswordHashed = Hash::make($newPassword);

        $updatedUser = User::where('id', $user->id)->first();

        // then the password should be updated successfully
        $this->assertEquals($newPasswordHashed, $updatedUser->password);
    }

    /** @test */
    public function confirm_password_does_not_match()
    {
        // given we have a user
        $user = factory(User::class)->create()->first();
        // and we're logged in as that user
        $this->actingAs($user);

        // when the user navigates to their profile editing page
        $response = $this->get('/profile/edit/' . $user->id);
        // and edits their password with a valid password (i.e. 6 or more characters)
        $existingPassword = 'secret';
        $newPassword = 'str0ngp4ssw0rd';
        // but enters an incorrect  password confirmation
        $newPasswordConfirmation = 'str0ngp4ssw0rdwhoops';
        
        $response = $this->followingRedirects()->post('/profile/edit-password/', [
            'current-password' => $existingPassword,
            'new-password' => $newPassword,
            'new-password-repeat' => $newPasswordConfirmation
        ]);

        $response->assertSeeText('New Passwords do not match!');
    }

    /** @test */
    public function user_enters_incorrect_current_password()
    {
        // ...
    }
   
}
