@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <h1><?php echo $title. ' <span class="orange">' . $data->name . '</span>'; ?></h1>
        </div>
    </div>
    <div class="row">
        <div class="container">
            <div class="row">
                <div class="col-md-12">

                    @if(isset($response))
                      @php( App\Helpers\Fixometer::printResponse($response))
                    @endif

                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-circle fa-lg"></i> Please be advised that your name and avatar will be shared on your group's public page on our website. (You can use a nickname or another image if you prefer.)
                    </div>

                    <form action="/user/edit/<?php echo $data->id; ?>" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" id="id" value="<?php echo $data->id; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo "has-error"; } ?>">
                                    <label for="name">User Name:</label>
                                    <input type="text" name="name" id="name" class="form-control" value="<?php echo $data->name; ?>">
                                    <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo '<span class="help-block text-danger">' . $error['name'] . '</span>'; } ?>
                                </div>
                                <div class="form-group <?php if(isset($error) && isset($error['email']) && !empty($error['email'])) { echo "has-error"; } ?>">
                                    <label for="email">Email:</label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?php echo $data->email; ?>">
                                    <?php if(isset($error) && isset($error['email']) && !empty($error['email'])) { echo '<span class="help-block text-danger">' . $error['email'] . '</span>'; } ?>
                                </div>


                                <div class="form-group <?php if(isset($error) && isset($error['password']) && !empty($error['password'])) { echo "has-error"; } ?>">
                                    <label for="new-password">New Password:</label>
                                    <input type="password" name="new-password" id="new-password" class="form-control">

                                    <label for="password-confirm">Confirm New Password:</label>
                                    <input type="password" name="password-confirm" id="password-confirm" class="form-control">


                                    <?php if(isset($error) && isset($error['password']) && !empty($error['password'])) { echo '<span class="help-block text-danger">' . $error['password'] . '</span>'; } ?>
                                </div>



                                @if(App\Helpers\Fixometer::hasRole($user, 'Administrator'))
                                <div class="form-group <?php if(isset($error) && isset($error['role']) && !empty($error['role'])) { echo "has-error"; } ?>">

                                    <label for="role">User Role:</label>
                                    <select id="role" name="role"  class="form-control selectpicker">
                                        <option></option>
                                        @foreach($roles as $role)
                                        <option value="<?php echo $role->id; ?>" <?php echo ($role->id == $data->role ? 'selected' : ''); ?>><?php echo $role->role; ?></option>
                                        @endforeach
                                    </select>
                                    <?php if(isset($error) && isset($error['role']) && !empty($error['role'])) { echo '<span class="help-block text-danger">' . $error['role'] . '</span>'; } ?>
                                </div>
                                @endif
                            </div>
                            <div class="col-md-6">

                                @if (App\Helpers\Fixometer::featureIsEnabled(env('FEATURE__LANGUAGE_SWITCHER')))
                                <div class="form-group">
                                  <label for="language">Language preference:</label>
                                  <select id="language" name="language"  class="form-control selectpicker">
                                      <option></option>
                                      @foreach($langs as $k => $l)
                                      <option value="<?php echo $k; ?>" <?php echo ($k == $data->language ? 'selected' : ''); ?>><?php echo $l; ?></option>
                                      @endforeach
                                  </select>
                                </div>
                                @endif
                                <div class="form-group">
                                    <label for="profile">Profile Picture:</label>
                                    <input type="file" class="form-control file" name="profile"data-show-upload="false" data-show-caption="true">
                                </div>


                                <?php $groupclass = (App\Helpers\Fixometer::hasRole($user, 'Administrator') ? 'show' : 'hidden'); ?>
                                <div class="form-group <?php echo $groupclass; ?> <?php if(isset($error) && isset($error['group']) && !empty($error['group'])) { echo "has-error"; } ?>">
                                    <label for="group">Group(s):</label>

                                        @foreach($groups as $group)
                                         <div class="checkbox">
                                            <label>
                                                <input
                                                    value="<?php echo $group->id; ?>"
                                                    type="checkbox"
                                                    name="groups[]"
                                                    id="group-<?php echo $group->id; ?>"
                                                    <?php echo (in_array($group->id, $data->groups) ? ' checked ' : ''); ?>
                                                >
                                                <?php echo $group->name; ?>
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
