<div class="edit-panel">

    <div class="form-row">
    <div class="col-lg-12">
        @if (Auth::id() == $user->id)
        <h3>@lang('general.profile')</h3>
        <p>@lang('general.profile_content')</p>
        @else
        <h4>{{ $user->name }}'s @lang('general.other_profile')</h4>
        <p>@lang('general.profile_content')</p>
        @endif
    </div>
    </div>
    <form action="/profile/edit-info" method="post">
    @csrf

    {{ Form::hidden('id', $user->id) }}

    <div class="form-row">
        <div class="form-group col-lg-6">
        <label for="name">@lang('profile.name'):</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}">
        </div>

        <div class="form-group col-lg-6">
            <label for="country">@lang('profile.country'):<sup>*</sup></label>
            <div class="form-control form-control__select">
                <select id="country" name="country" required aria-required="true" class="field select2">
                    <option value=""></option>
                    @foreach (App\Helpers\Fixometer::getAllCountries() as $key => $value)
                        @if ($user->country_code == $key)
                        <option value="{{ $key }}" selected>{{ $value }}</option>
                        @else
                        <option value="{{ $key }}">{{ $value }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="invalid-feedback">@lang('registration.country_validation')</div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-lg-6">
        <label for="email">@lang('profile.email_address'):</label>
        <input type="text" class="form-control" id="email" name="email" value="{{ $user->email }}">
        </div>
        <div class="form-group col-lg-6">
        <label for="townCity">@lang('registration.town-city'):</label>
        <input type="text" class="form-control" id="townCity" name="townCity" value="{{ $user->location }}">
        </div>
    </div>
    <div class="form-row">

        <div class="form-group col-lg-6">
            <label for="age">@lang('registration.age'):</label>
            <div class="form-control form-control__select">
                <select id="age" name="age" required aria-required="true" class="field select2">
                    @foreach(App\Helpers\Fixometer::allAges() as $age)
                    @if ( $user->age == $age )
                        <option value="{{ $age }}" selected>{{ $age }}</option>
                    @else
                        <option value="{{ $age }}">{{ $age }}</option>
                    @endif
                    @endforeach
                </select>
            </div>
            <div class="invalid-feedback">@lang('registration.age_validation')</div>
        </div>

        <div class="form-group col-lg-6">
        <label for="gender">@lang('registration.gender'):</label>
        <input id="gender" class="form-control" name="gender" value="{{ $user->gender }}">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-lg-12">
        <label for="biography">@lang('profile.biography'):</label>
        <textarea class="form-control" id="biography" name="biography" rows="8" cols="80">{{ $user->biography }}</textarea>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-lg-12">
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">@lang('profile.save_profile')</button>
        </div>
        </div>
    </div>
    </form>

    </div>
<!-- / edit-panel -->


<div class="row row-end">

    <div class="col-lg-6 d-flex col-bottom" id="repair-skills">
    <div class="edit-panel">
        <h4>@lang('general.repair_skills')</h4>
        <p>@lang('general.repair_skills_content')</p>
        <form action="/profile/edit-tags" method="post">

        @csrf

        {{ Form::hidden('id', $user->id) }}

        <div class="form-group">
            <label for="tags[]">@lang('general.your_repair_skills'):</label>
            <div class="form-control form-control__select">
            <select id="tags" name="tags[]" class="select2-tags" multiple>
                @foreach( App\Helpers\Fixometer::skillCategories() as $key => $skill_category )
                    <optgroup label="@lang($skill_category)">
                    @foreach ($skills[$key] as $skill)
                        @if ( !empty($user_skills) && in_array($skill->id, $user_skills))
                        <option value="{{ $skill->id }}" selected>@lang($skill->skill_name)</option>
                        @else
                        <option value="{{ $skill->id }}">@lang($skill->skill_name)</option>
                        @endif
                    @endforeach
                    </optgroup>
                @endforeach
            </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-lg-12">
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">@lang('general.save_repair_skills')</button>
            </div>
            </div>
        </div>

        </form>

    </div>
    <!-- / edit-panel -->

    </div>

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
