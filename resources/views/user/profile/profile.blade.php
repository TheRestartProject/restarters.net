<div class="vue">
  <ProfileInfoTab />
</div>

<div class="row row-end">

    <div class="col-lg-6 d-flex col-bottom" id="repair-skills">
    <div class="vue">
      <SkillsTab />
    </div>
    </div>

    <div class="col-lg-6 d-flex col-bottom" id="change-photo">
    <div class="vue">
        @php
            $path = $user->getProfile($user->id)->path ?? null;
            $currentPhotoUrl = is_null($path) ? null : asset('/uploads/thumbnail_' . $path);
        @endphp
        <ProfilePhotoTab current-photo-url="{{ $currentPhotoUrl }}" />
    </div>
    </div>
</div>
