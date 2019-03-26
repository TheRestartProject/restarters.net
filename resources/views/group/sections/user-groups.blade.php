<section class="table-section" id="user-groups">
  <h2>@lang('groups.groups_title1')</h2>
  <div class="table-responsive">
    <table role="table" class="table table-striped table-hover">
      @include('partials.tables.head-groups')
      <tbody>
        @if( !$your_groups->isEmpty() )
          @foreach ($your_groups as $group)
            @include('partials.tables.row-groups')
          @endforeach
        @else
          <tr>
            <td colspan="13" align="center" class="p-3">
              You are not associated with any groups, take a look and see if there's one you would like to join
              @if( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host') )
                <br><a href="/group/all">See all groups</a>
              @endif
            </td>
          </tr>
        @endif
      </tbody>
    </table>
  </div>
</section>
