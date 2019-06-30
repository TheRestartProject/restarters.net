<section class="table-section" id="user-groups">
  <h2>@lang('groups.groups_title1')</h2>
  <div class="table-responsive">
    <table role="table" class="table table-striped table-hover table-layout-fixed">
      @include('partials.tables.head-groups')
      <tbody>
        @if( !$your_groups->isEmpty() )
          @foreach ($your_groups as $group)
            @include('partials.tables.row-groups')
          @endforeach
        @else
            <tr>
            <td colspan="13" align="center" class="p-3">
                @lang('groups.not_joined_a_group')
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
