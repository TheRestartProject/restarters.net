<div class="edit-panel">

    <div class="form-row">
    <div class="col-lg-6">
        <h4>@lang('auth.change_password')</h4>
        <p>@lang('auth.change_password_text')</p>
    </div>
    </div>

    <form action="/profile/edit-password" method="post">
    @csrf

    {{ Form::hidden('id', $user->id) }}

    <fieldset class="registration__offset2">
        <div class="form-row">
        <div class="form-group col-lg-6">
            <label for="current-password">@lang('auth.current_password'):</label>
            <input type="password" class="form-control" id="current-password" name="current-password">
        </div>
        </div>
        <div class="form-row">
        <div class="form-group col-lg-6">
            <label for="new-password">@lang('auth.new_password'):</label>
            <input type="password" class="form-control" id="new-password" name="new-password">
        </div>
        <div class="form-group col-lg-6">
            <label for="new-password-repeat">@lang('auth.new_repeat_password'):</label>
            <input type="password" class="form-control" id="new-password-repeat" name="new-password-repeat">
        </div>
        </div>
    </fieldset>

    <div class="form-row">
        <div class="form-group col-lg-12">
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">@lang('auth.change_password')</button>
        </div>
        </div>
    </div>
    </form>


</div>

<div class="edit-panel">
    <div class="form-row">
        <div class="col">
            <h4>@lang('profile.language_panel_title')</h4>
        </div>
    </div>

    <form action="/profile/edit-language" method="post">
        @csrf

        {{ Form::hidden('id', $user->id) }}

        <fieldset class="language">
            <div class="form-row">
            <div class="form-group col-lg-6">
                <label for="user_language">@lang('profile.preferred_language')</label>
                <select class="form-control" id="user_language" name="user_language">
                    @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                        @if (isset($user->language) && $localeCode == $user->language)
                        <option value="{{ $localeCode }}" selected>{{ $properties['native'] }}</option>
                        @else
                        <option value="{{ $localeCode }}">{{ $properties['native'] }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            </div>
        </fieldset>

        <div class="form-row">
            <div class="form-group col-lg-12">
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </form>
</div>

@if (App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator'))
    <div class="edit-panel">

    <div class="form-row">
        <div class="col-lg-6">
        <h4>@lang('auth.profile_admin')</h4>
        <p>@lang('auth.profile_admin_text')</p>
        </div>
    </div>

    <form action="/profile/edit-admin-settings" method="post">
        @csrf

        {{ Form::hidden('id', $user->id) }}

        <fieldset class="user_role">
        <div class="form-row">
            <div class="form-group col-lg-6">
            <label for="user_role">@lang('auth.user_role'):</label>
            <select class="form-control" id="user_role" name="user_role">
                <option value="" selected>Choose role</option>
                @foreach (App\Helpers\Fixometer::allRoles() as $r)
                @if (isset($user->role) && $r->idroles == $user->role)
                    <option value="{{ $r->idroles }}" selected>{{ $r->role }}</option>
                @else
                    <option value="{{ $r->idroles }}">{{ $r->role }}</option>
                @endif
                @endforeach
            </select>
            </div>
            <div class="form-group col-lg-6">
            <label for="assigned_groups">@lang('auth.assigned_groups'):</label>
            <select id="assigned_groups" name="assigned_groups[]" class="form-control" multiple size="8" data-live-search="true" title="Choose groups...">
                @if(isset($all_groups))
                @foreach($all_groups as $g)
                    @if (!empty($user_groups) && in_array($g->idgroups, $user_groups))
                    <option value="<?php echo $g->idgroups; ?>" selected><?php echo $g->name; ?></option>
                    @else
                    <option value="<?php echo $g->idgroups; ?>"><?php echo $g->name; ?></option>
                    @endif
                @endforeach
                @endif
            </select>
            </div>

            <div class="form-group col-lg-6 pr-0">
            <label>Preferences:</label>
            @foreach($all_preferences as $preference)
                <div class="form-group form-check">
                <input @if(in_array($preference->id, $user_preferences)) checked @endif type="checkbox" class="form-check-input" id="preference-{{ $preference->id }}" name="preferences[]" value="{{ $preference->id }}">
                <label class="form-check-label" for="preference-{{ $preference->id }}">
                    {{ $preference->name }}
                </label>
                @if( !empty($preference->purpose) )
                    <small class="form-text text-muted">{{{ $preference->purpose }}}</small>
                @endif
                </div>
            @endforeach
            </div>

            <div class="form-group col-lg-6">
            <label>Permissions:</label>
            @foreach($all_permissions as $permission)
                <div class="form-group form-check">
                <input @if(in_array($permission->idpermissions, $user_permissions)) checked @endif type="checkbox" class="form-check-input" id="permission-{{ $permission->idpermissions }}" name="permissions[]" value="{{ $permission->idpermissions }}">
                <label class="form-check-label" for="permission-{{ $permission->idpermissions }}">
                    {{ $permission->permission }}
                </label>
                @if( !empty($permission->purpose) )
                    <small class="form-text text-muted">{{{ $permission->purpose }}}</small>
                @endif
                </div>
            @endforeach
            </div>


        </fieldset>

        <div class="form-row">
        <div class="form-group col-lg-12">
            <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">@lang('auth.save_user')</button>
            </div>
        </div>
        </div>
    </form>


    </div>
@endif

<form action="/user/soft-delete" method="post" id="delete-form">
    @csrf

    {{ Form::hidden('id', $user->id) }}

    <div class="alert alert-danger" role="alert">
    <div class="row">
        <div class="col-md-8 d-flex flex-column align-content-center">@lang('auth.delete_account_text')</div>
        <div class="col-md-4 d-flex flex-column align-content-center"><button type="submit" class="btn btn-danger" id="delete-form-submit">
    @lang('auth.delete_account')</div>
    </div>


    </button>
    </div>

</form>
