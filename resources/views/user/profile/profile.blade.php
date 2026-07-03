<div class="vue">
  <ProfileInfoTab />
</div>

<div class="row row-end">

    <div class="col-lg-6 d-flex col-bottom" id="repair-skills">
    <div class="vue">
      <SkillsTab />
    </div>
    </div>

    <!-- The "change photo" upload form is intentionally left as legacy Blade/jQuery for now,
         pending a security review of the upload surface (file handling, validation, storage)
         before it is migrated to the Vue/API v2 pattern used elsewhere on this page. -->
    <div class="col-lg-6 d-flex col-bottom" id="change-photo">
    <div class="edit-panel">
        <h4>@lang('profile.change_photo')</h4>
        <p>@lang('profile.change_photo')</p>
        <form action="/profile/edit-photo" method="post" enctype="multipart/form-data">
        @csrf

        {{ Form::hidden('id', $user->id) }}

        <div class="form-row">
            <div class="form-group col-lg-12">
            <label for="profilePhoto">@lang('profile.profile_picture'):</label>
            <input type="file" class="form-control" id="profilePhoto" name="profilePhoto">
            <!-- <input type="file" class="form-control file" name="profile"data-show-upload="false" data-show-caption="true"> -->
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-lg-4">
            @php ( $path = $user->getProfile($user->id)->path )
            @if ( !is_null($path) )
                <img width="50" src="{{ asset('/uploads/thumbnail_' . $path) }}" alt="{{{ $user->name }}}'s avatar">
            @endif
            </div>
            <div class="form-group col-lg-8">
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">@lang('profile.change_photo')</button>
            </div>
            </div>
        </div>
        </form>
    </div>
    <!-- / edit-panel -->

    </div>
</div>
