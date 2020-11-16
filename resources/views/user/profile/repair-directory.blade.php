<div class="edit-panel">

  <div class="form-row">
    <div class="col-lg-12">
      <h4>@lang('profile.repair_directory')</h4>
    </div>
  </div>

  <form action="/profile/edit-repair-directory" method="post">
    @csrf

    {{ Form::hidden('id', $user->id) }}

    @php(error_log("Edit profile on {$user->id}"))

    @php($role = $user->repairdir_role)

    <fieldset>
      <div class="form-group row justify-content-center">
        <label for="role" class="col-lg-6">
          @lang('profile.repair_dir_role')
        </label>
        <div class="col-lg-6">
          <div class="form-control form-control__select">
            <select id="role" name="role" required aria-required="true" class="field select2">
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_NONE; ?>" @if ($role === \App\Role::REPAIR_DIRECTORY_NONE) selected @endif>@lang('profile.repair_dir_none')</option>
              @if(Auth::user()->isRepairDirectoryRegionalAdmin() || Auth::user()->isRepairDirectorySuperAdmin())
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_EDITOR; ?>" @if ($role === \App\Role::REPAIR_DIRECTORY_EDITOR) selected @endif>@lang('profile.repair_dir_editor')</option>
              @else
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_EDITOR; ?>" @if ($role === \App\Role::REPAIR_DIRECTORY_EDITOR) selected @endif disabled>@lang('profile.repair_dir_editor')</option>
              @endif
              @if(Auth::user()->isRepairDirectorySuperAdmin())
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_REGIONAL_ADMIN; ?>" @if ($role === \App\Role::REPAIR_DIRECTORY_REGIONAL_ADMIN) selected @endif>@lang('profile.repair_dir_regional_admin')</option>
              @else
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_REGIONAL_ADMIN; ?> @if ($role === \App\Role::REPAIR_DIRECTORY_REGIONAL_ADMIN) selected @endif" disabled>@lang('profile.repair_dir_regional_admin')</option>
              @endif
              @if(Auth::user()->isRepairDirectorySuperAdmin())
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_SUPERADMIN; ?>" @if ($role === \App\Role::REPAIR_DIRECTORY_SUPERADMIN) selected @endif>@lang('profile.repair_dir_superadmin')</option>
              @else
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_SUPERADMIN; ?>" @if ($role === \App\Role::REPAIR_DIRECTORY_SUPERADMIN) selected @endif disabled>@lang('profile.repair_dir_superadmin')</option>
              @endif
            </select>
          </div>
        </div>
      </div>
    </fieldset>

    <div class="button-group row">
      <div class="offset-9 col-sm-3 d-flex align-items-center justify-content-end">
        <button class="btn btn-primary btn-save">@lang('auth.save_user')</button>
      </div>
    </div>

  </form>

</div>
