<div class="edit-panel">

  <div class="form-row">
    <div class="col-lg-12">
      <h4>@lang('profile.repair_directory')</h4>
    </div>
  </div>

  <form action="/profile/repair-directory" method="post">
    @csrf

    {{ Form::hidden('id', $user->id) }}

    <fieldset class="email-options">
      <div class="form-group row justify-content-center">
        <label for="role" class="col-lg-6">
          @lang('profile.repair_dir_role')
        </label>
        <div class="col-lg-6">
          <div class="form-control form-control__select">
            <select id="role" name="role" required aria-required="true" class="field select2">
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_NONE; ?>">@lang('profile.repair_dir_none')</option>
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_EDITOR; ?>">@lang('profile.repair_dir_editor')</option>
              @if(Auth::user()->isRepairDirectoryRegionalAdmin() || Auth::user()->isRepairDirectorySuperAdmin())
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_REGIONAL_ADMIN; ?>">@lang('profile.repair_dir_regional_admin')</option>
              @else
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_REGIONAL_ADMIN; ?>" disabled>@lang('profile.repair_dir_regional_admin')</option>
              @endif
              @if(Auth::user()->isRepairDirectorySuperAdmin())
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_SUPERADMIN; ?>"@lang('profile.repair_dir_superadmin')</option>
              @else
              <option value="<?php echo \App\Role::REPAIR_DIRECTORY_SUPERADMIN; ?>" disabled>@lang('profile.repair_dir_superadmin')</option>
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
