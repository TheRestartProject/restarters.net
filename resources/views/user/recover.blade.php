@extends('layouts.app')

@section('content')
<div class="login-form">
    <div class="text-center">
        <img src="/images/logo_mini.png" alt="The Restart Project">
    </div>
    <p class="login-text"><span class=\"patua-blue\">Welcome to our community space,</span> where you can share upcoming Restart Parties and track their social and environmental impact. By doing so, we can empower and motivate at a local level, but also build global momentum for a change.</p>
    @if(isset($response))
    @php( App\Helpers\Fixometer::printResponse($response) )
    @endif
    <div class="shader"></div>
    <h2><span class="title-text">recover your password</span></h2>

    <p class="explainer-text">Please input the email address that was used when you first registered with the Fixometer. The system will send you an email with instructions on how to recover the access to your account.</p>

    <form class="" method="post" action="/user/recover">
        @csrf
        <div class="form-group">
            <label for="email" class="text-center">email</label>
            <input type="email" class="form-control" name="email" id="email" placeholder="Your email...">
        </div>

        <div class="form-group text-center">
            <button type="submit" class="form-control btn btn-primary login-button" name="submit" id="submit"><i class="fa fa-sign-in"></i><span class="sr-only">Login</span></button>
        </div>
    </form>
</div>
@endsection