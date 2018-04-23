<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\User;

use Auth;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $user = User::find(Auth::id());

        return view('user.profile', [
          'user' => $user,
        ]);
    }

    public function postEdit(Request $request) {

      $user = User::find($request->input('id'));

      $check_password = Hash::check($request->input('password'), $user->password);

      if ( !is_null($request->input('new-password')) && !$check_password ) {
        return redirect()
                ->back()
                  ->withErrors('Incorrect old password - please try again');
      }

      $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|unique:users,email,'.$user->id.'|max:255',
            'location' => 'max:191',
            'new-password' => 'confirmed'
      ])->validate();

      $user->name = $request->input('name');
      $user->email = $request->input('email');
      $user->age = $request->input('age');
      $user->gender = $request->input('gender');
      $user->location = $request->input('location');
      if (!empty($request->input('new-password'))) {
        $user->setPassword(Hash::make($request->input('new-password')));
      }
      $user->save();

      return redirect()->back()->with('success', 'Profile updated');

    }
}
