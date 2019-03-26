<section class="table-section" id="groups-near">
  <h2>@lang('groups.groups_title2') <sup>(<a href="/group/all">See all groups</a>)</sup></h2>
  <div class="table-responsive">
    <table role="table" class="table table-striped table-hover">
      <thead>
        <tr>
          <th width="42"></th>
          <th width="200" scope="col"><label for="label-name">@lang('groups.groups_name')</label></th>
          <th width="200" scope="col"><label for="label-location">@lang('groups.groups_location')</label></th>
          <th width="75" scope="col" class="text-center"><label for="label-hosts">@lang('groups.groups_hosts')</label></th>
          <th width="75" scope="col" class="text-center"><label for="label-restarters">@lang('groups.groups_restarters')</label></th>
        </tr>
      </thead>
      <tbody>
        @if( !is_null($groups_near_you) && count($groups_near_you) > 0 )
          @foreach ($groups_near_you as $group)
            @include('partials.tables.row-groups')
          @endforeach
        @else
          <tr>
            <td colspan="13" align="center" class="p-3">
              @if ($your_area)
                There are currently no groups near to your area ({{ $your_area }}). Would you consider starting a group?
                <br><a href="/group/create/">@lang('groups.create_groups')</a>
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
