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
<?php
        /** select random bkground **/
        $rand = rand(1, 4);
        ?>
        <div class="login-deets bg_<?php echo $rand; ?>">


                <div class="detail-wrap">
                    <div class="detail">
                        <h4>Devices Restarted</h4>
                        <span class="big-number">
                            <span id="devices-restarted-value" class="big-number"><?php echo number_format($devices[0]->counter, 0, '-', ','); ?></span>
                        </span>
                    </div>

                    <div class="detail">
                        <h4>CO<sub>2</sub> Emission prevented</h4>
                        <span id="co2-prevented-value" class="big-number"><?php echo number_format($weights[0]->total_footprints, 0, '-', ','); ?> kg</span>
                    </div>
                    <div class="detail">
                        <h4>Waste prevented</h4>
                        <span id="waste-prevented-value" class="big-number"><?php echo number_format($weights[0]->total_weights, 0, '-', ','); ?> kg</span>
                    </div>


                    <div class="detail">
                        <h4>Parties thrown</h4>
                        <span id="parties-thrown-value" class="big-number"><?php echo count($allparties) - count($nextparties); ?></span>
                    </div>

                </div>

        </div>
<div class="container">

            <div class="row">
                <div class="col-xs-12 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">

                </div>

            </div>
        </div>
@endsection
