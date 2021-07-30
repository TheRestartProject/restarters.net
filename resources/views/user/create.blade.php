@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-4">
            <h1><?php echo $title; ?></h1>
        </div>
    </div>
    <div class="row">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    @if(isset($response))
                      @php( App\Helpers\Fixometer::printResponse($response) )
                    @endif

                    <form action="/user/create" method="post" enctype="multipart/form-data">
                      @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo "has-error"; } ?>">
                                    <label for="name">User Name:</label>
                                    <input type="text" name="name" id="name" class="form-control">
                                    <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo '<span class="help-block text-danger">' . $error['name'] . '</span>'; } ?>
                                </div>
                                <div class="form-group <?php if(isset($error) && isset($error['email']) && !empty($error['email'])) { echo "has-error"; } ?>">
                                    <label for="email">Email:</label>
                                    <input type="email" name="email" id="email" class="form-control">
                                    <?php if(isset($error) && isset($error['email']) && !empty($error['email'])) { echo '<span class="help-block text-danger">' . $error['email'] . '</span>'; } ?>
                                </div>

                                <div class="form-group <?php if(isset($error) && isset($error['role']) && !empty($error['role'])) { echo "has-error"; } ?>">

                                    <label for="role">User Role:</label>
                                    <select id="role" name="role"  class="form-control selectpicker"> 
                                        <option></option>
                                        @foreach($roles as $role)
                                        <option value="<?php echo $role->id; ?>"><?php echo $role->role; ?></option>
                                        @endforeach
                                    </select>
                                    <?php if(isset($error) && isset($error['role']) && !empty($error['role'])) { echo '<span class="help-block text-danger">' . $error['role'] . '</span>'; } ?>
                                </div>

                                <div class="form-group">
                                    <label for="profile">Profile Picture:</label>
                                    <input type="file" class="form-control file" name="profile" data-show-upload="false" data-show-caption="true">
                                </div>

                            </div>
                            <div class="col-md-6">
                              <?php /*

                                <div class="form-group <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo "has-error"; } ?>">
                                    <label for="password">Password:</label>
                                    <input type="password" name="password" id="password" class="form-control">
                                    <?php if(isset($error) && isset($error['password']) && !empty($error['password'])) { echo '<span class="help-block text-danger">' . $error['password'] . '</span>'; } ?>
                                </div>
                                <div class="form-group">
                                    <label for="c_password">Confirm Password:</label>
                                    <input type="password" name="c_password" id="c_password" class="form-control">
                                </div>

                                */ ?>

                                <div class="form-group <?php if(isset($error) && isset($error['group']) && !empty($error['group'])) { echo "has-error"; } ?>">
                                    <label for="group">Group(s):</label>
                                        @foreach($groups as $group)
                                        <div class="checkbox">
                                            <label>
                                                <input value="<?php echo $group->id; ?>" type="checkbox" name="groups[]" id="group-<?php echo $group->id; ?>"> <?php echo $group->name; ?>
                                            </label>
                                        </div>

                                        @endforeach
                                        <?php if(isset($error) && isset($error['group']) && !empty($error['group'])) { echo '<span class="help-block text-danger">' . $error['group'] . '</span>'; } ?>
                                </div>

                                <div class="form-group">
                                    <button class="btn btn-default" type="reset"><i class="fa fa-refresh"></i> reset</button>
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> save</button>

                                </div>


                            </div>
                        </div>


                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
