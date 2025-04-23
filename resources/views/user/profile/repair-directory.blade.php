<div class="edit-panel">

  <div class="form-row">
    <div class="col-lg-12">
      <h4>@lang('profile.repair_directory')</h4>
    </div>
  </div>

  <form action="/profile/edit-repair-directory" method="post">
    @csrf

    {{ Form::hidden('id', $user->id) }}

    <?php
      $roles = [];
      
      $roles[\App\Models\Role::REPAIR_DIRECTORY_NONE] = [
        'selected' => $user->isRepairDirectoryNone(),
        'disabled' => !Auth::user()->can('changeRepairDirRole', [ $user, \App\Models\Role::REPAIR_DIRECTORY_NONE ]),
        'name' => __('profile.repair_dir_none')
      ];
      $roles[\App\Models\Role::REPAIR_DIRECTORY_EDITOR] = [
        'selected' => $user->isRepairDirectoryEditor(),
        'disabled' => !Auth::user()->can('changeRepairDirRole', [ $user, \App\Models\Role::REPAIR_DIRECTORY_EDITOR ]),
        'name' => __('profile.repair_dir_editor')
      ];
      $roles[\App\Models\Role::REPAIR_DIRECTORY_REGIONAL_ADMIN] = [
        'selected' => $user->isRepairDirectoryRegionalAdmin(),
        'disabled' => !Auth::user()->can('changeRepairDirRole', [ $user, \App\Models\Role::REPAIR_DIRECTORY_REGIONAL_ADMIN ]),
        'name' => __('profile.repair_dir_regional_admin')
      ];
      $roles[\App\Models\Role::REPAIR_DIRECTORY_SUPERADMIN] = [
        'selected' => $user->isRepairDirectorySuperAdmin(),
        'disabled' => !Auth::user()->can('changeRepairDirRole', [ $user, \App\Models\Role::REPAIR_DIRECTORY_SUPERADMIN ]),
        'name' => __('profile.repair_dir_superadmin')
      ];

    ?>

    <fieldset>
      <div class="form-group row justify-content-center">
        <label for="role" class="col-lg-6">
          @lang('profile.repair_dir_role')
        </label>
        <div class="col-lg-6">
          <div class="form-control form-control__select">
            <select id="role" name="role" required aria-required="true" class="field select2">
              <?php

              foreach ($roles as $role => $info) {
                echo "<option value=\"$role\"" .
                   ($info['selected'] ? " selected" : "") .
                   ($info['disabled'] ? " disabled" : "") .
                  ">{$info['name']}</option>";
              }

              ?>
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
