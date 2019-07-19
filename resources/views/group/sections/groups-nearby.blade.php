<section class="table-section" id="groups-near">
  <h2>@lang('groups.groups_title2') <sup>(<a href="/group/all">See all groups</a>)</sup></h2>
  <div class="table-responsive">
    <table role="table" class="table table-striped table-hover table-layout-fixed">
      @include('partials.tables.head-groups')
      <tbody>
        @if( !is_null($groups_near_you) && count($groups_near_you) > 0 )
          @foreach ($groups_near_you as $group)
            @include('partials.tables.row-groups')
          @endforeach
        @else
            <tr>
                <td colspan="13" align="center" class="p-3">
                    @if ($your_area)
                        <p>
                        @lang('groups.no_groups_near_you', ['area' => $your_area])
                        </p>
                        <p>
                        @lang('groups.consider_starting_a_group', ['resources_url' => env('DISCOURSE_URL').'/session/sso?return_path='.env('DISCOURSE_URL').'/t/how-to-power-up-community-repair-with-restarters-net/1228/'])
                        </p>
                @else
                You do not currently have a town/city set.  You can set one in <a href="/profile/edit/{{ Auth::user()->id }}">your profile</a>.
                @endif
            </td>
            </tr>
        @endif
      </tbody>
    </table>
  </div>
</section>
