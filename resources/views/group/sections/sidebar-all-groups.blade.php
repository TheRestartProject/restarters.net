<div class="collapse d-lg-block fixed-overlay-md" id="collapseFilter">

  <div class="form-row d-lg-none">
    <div class="form-group col mobile-search-bar-md">
      <button type="button" class="d-lg-none mobile-search-bar-md__close" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter"><svg width="21" height="21" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Close</title><g><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></svg></button>
    </div>
  </div>
  <aside class="edit-panel edit-panel__side">
      <!-- <legend>@lang('groups.by_details')</legend> -->
    <div class="form-group">
      <label for="name">@lang('groups.groups_name'):</label>
      @if(isset($name))
        <input type="text" name="name" class="form-control" placeholder="@lang('groups.search_name')" value="{{ $name }}"/>
      @else
        <input type="text" name="name" class="form-control" placeholder="@lang('groups.search_name')"/>
      @endif
    </div>

    @if( FixometerHelper::hasRole(Auth::user(), 'Administrator'))
    <div class="form-group">
      <label for="tags">@lang('groups.group_tag'):</label>
      <div class="form-control form-control__select">
        <select id="tags" name="tags[]" class="form-control select2-tags" multiple data-live-search="true" title="Choose group tags...">
          @foreach ($all_group_tags as $group_tag)
            @if(isset($selected_tags) && in_array($group_tag->id, $selected_tags))
              <option value="{{ $group_tag->id }}" selected>{{ $group_tag->tag_name }}</option>
            @else
              <option value="{{ $group_tag->id }}">{{ $group_tag->tag_name }}</option>
            @endif
          @endforeach
        </select>
      </div>
    </div>
    @endif

    <div class="form-group">
      <label for="location">@lang('groups.group_town-city'):</label>
      @if(isset($location))
        <input type="text" name="location" class="form-control" placeholder="@lang('groups.town-city-placeholder')" value="{{ $location }}"/>
      @else
        <input type="text" name="location" class="form-control" placeholder="@lang('groups.town-city-placeholder')"/>
      @endif
    </div>

    <div class="form-group">
        <label for="network">Network:</label>
        <div class="form-control form-control__select">
            <select id="network" name="network" class="field select2">
                <option value=""></option>
                @foreach ($networks as $network)
                    @if( isset($selected_network) && $network->id == $selected_network )
                        <option selected value="{{ $network->id }}">{{ $network->name }}</option>
                    @else
                        <option value="{{ $network->id }}">{{ $network->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-group">
      <label for="country">@lang('groups.group_country'):</label>
      <div class="form-control form-control__select">
        <select id="country" name="country" class="field select2">
          <option value=""></option>
          @foreach (FixometerHelper::getAllCountries() as $country_code => $country_name)
            @if( isset($selected_country) && $country_name == $selected_country )
              <option selected value="{{ $country_name }}">{{ $country_name }}</option>
            @else
              <option value="{{ $country_name }}">{{ $country_name }}</option>
            @endif
          @endforeach
        </select>
      </div>
    </div>
    <button class="btn btn-secondary btn-groups w-100" type="submit">@lang('groups.search_groups')</button>
  </aside>
</div><!-- /collapseFilter -->
