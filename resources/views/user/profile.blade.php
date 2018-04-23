@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Profile</div>

                <div class="card-body">
                    @if (\Session::has('success'))
                        <div class="alert alert-success">
                            {!! \Session::get('success') !!}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{ Form::open(['url' => '/edit-user'])}}
                      {{ Form::token() }}

                      {{ Form::hidden('id', $user->id) }}

                      <h4>Personal Information:</h4>
                      <div class="form-group">
                        <div class="col-md-8 pb-3">
                          {{ Form::label('name', 'Name:') }}
                          {{ Form::text('name', $user->name, ['class' => 'form-control required']) }}
                        </div>

                        <div class="col-md-8 pb-3">
                          {{ Form::label('email', 'Email:') }}
                          {{ Form::text('email', $user->email, ['class' => 'form-control required']) }}
                        </div>

                        <div class="col-md-8 pb-3">
                          {{ Form::label('age', 'Age:') }}
                          {{ Form::select('age', FixometerHelper::allAges(), $user->age, ['class' => 'form-control']) }}
                        </div>

                        <div class="col-md-8 pb-3">
                          {{ Form::label('gender', 'Gender:') }}
                          {{ Form::select('gender', ['N/A' => 'N/A', 'Male' => 'Male', 'Female' => 'Female' ], $user->gender, ['class' => 'form-control']) }}
                        </div>

                        <div class="col-md-8 pb-3">
                          {{ Form::label('location', 'Location:') }}
                          {{ Form::text('location', $user->location, ['class' => 'form-control']) }}
                        </div>
                      </div>

                      <h4>Change Password:</h4>
                      <div class="form-group">
                        <div class="col-md-8 pb-3">
                          {{ Form::label('password', 'Old Password:') }}
                          {{ Form::text('password', null, ['class' => 'form-control']) }}
                        </div>
                        <div class="col-md-8 pb-3">
                          {{ Form::label('new-password', 'New Password:') }}
                          {{ Form::text('new-password', null, ['class' => 'form-control']) }}
                        </div>
                        <div class="col-md-8 pb-3">
                          {{ Form::label('new-password_confirmation', 'Confirm New Password:') }}
                          {{ Form::text('new-password_confirmation', null, ['class' => 'form-control']) }}
                        </div>
                      </div>

                      {{ Form::submit('Save Profile', ['class' => 'btn btn-primary pull-right']) }}

                    {{ Form::close() }}

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
