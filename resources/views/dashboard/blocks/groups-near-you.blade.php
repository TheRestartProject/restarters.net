<section class="dashboard__block">
    <div class="dashboard__block__header dashboard__block__header--events">
        <h4>@lang('dashboard.groups_near_you_header')</h4>
    </div>
    <div class="dashboard__block__content dashboard__block__content--table">
      <p>@lang('dashboard.groups_near_you_text')</p>
        <div class="table-responsive">
        <table role="table" class="table table-events table-striped">
            <tbody>
              @if ( ! is_null($groupsNearYou) && !$groupsNearYou->isEmpty() )
                @foreach($groupsNearYou as $group)
                  <tr>
                      <td class="table-cell-icon">
                          @php( $group_image = $group->groupImage )
                          @if( is_object($group_image) && is_object($group_image->image) )
                              <img style="display:inline-block;" src="{{ asset('/uploads/thumbnail_' . $group_image->image->path) }}" alt="{{{ $group->name }}}" title="{{{ $group->name }}}" />
                          @else
                              <img style="display:inline-block;padding-right:5px" src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{{ $group->name }}}">
                          @endif

                      </td>
                      <td class="cell-name">
                          <a href="/group/view/{{ $group->idgroups }}">{{ $group->name }}</a>
                          <br/>
                          {{ $group->area }}
                      </td>
                      <td class="cell-date">
                          <a class="btn btn-primary" href="/group/join/{{ $group->idgroups }}" id="join-group">@lang('dashboard.groups_near_you_follow_action')</a>
                      </td>
                  </tr>
                @endforeach
              @else
                <tr>
                    <td colspan="4" style="text-align: center">
                        <p>@lang('dashboard.groups_near_you_none_nearby')</p>
                        <p>
                        @if (is_null(Auth::user()->location))
                            @lang('dashboard.groups_near_you_set_location', ['profile_url' => '/profile/edit/'.Auth::user()->id])
                        @else
                            @lang('dashboard.groups_near_you_your_location_is', ['location' => Auth::user()->location.', '.\App\Helpers\Fixometer::getCountryFromCountryCode(Auth::user()->country_code)])
                        @endif
                        </p>
                        <p>
                            @lang('dashboard.groups_near_you_start_a_group', ['resources_url' => env('DISCOURSE_URL').'/session/sso?return_path='.env('DISCOURSE_URL').'/t/how-to-power-up-community-repair-with-restarters-net/1228/'])
                        </p>
                    </td>
                </tr>
              @endif
            </tbody>
        </table>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="{{ route('groups') }}">@lang('dashboard.groups_near_you_see_more')</a>
        </div>
    </div>
</section>
