@if( !is_null($groups) )
  <section class="table-section" id="all-groups">
    <h2>@lang('groups.groups_title3')</h2>

    <input type="hidden" name="sort_direction" value="{{$sort_direction}}" class="sr-only">
    <input type="radio" name="sort_column" value="name" @if( $sort_column == 'name' ) checked @endif id="label-name" class="sr-only">
    <input type="radio" name="sort_column" value="distance" @if( $sort_column == 'distance' ) checked @endif id="label-location" class="sr-only">
    <input type="radio" name="sort_column" value="hosts" @if( $sort_column == 'hosts' ) checked @endif id="label-hosts" class="sr-only">
    <input type="radio" name="sort_column" value="restarters" @if( $sort_column == 'restarters' ) checked @endif id="label-restarters" class="sr-only">
    <input type="radio" name="sort_column" value="created_at" @if( $sort_column == 'created_at' ) checked @endif id="label-created" class="sr-only">

    <div class="table-responsive">
      <table role="table" class="table table-striped table-hover" id="sort-table">
        <thead>
          <tr>
            <th width="42">
            </th>

            <th width="200" scope="col">
              <label for="label-name"  class="sort-column @if( $sort_direction == 'ASC' && $sort_column == 'name' ) sort-column-asc @endif">
                @lang('groups.groups_name')
              </label>
            </th>

            <th width="200" scope="col">
              <label for="label-location" class="sort-column @if( $sort_direction == 'ASC' && $sort_column == 'distance' ) sort-column-asc @endif">
                @lang('groups.groups_location')
              </label>
            </th>

            <th width="75" scope="col" class="text-center">
              <label for="label-hosts" class="sort-column @if( $sort_direction == 'ASC' && $sort_column == 'hosts' ) sort-column-asc @endif">
                @lang('groups.groups_hosts')
              </label>
            </th>

            <th width="100" scope="col" class="text-center">
              <label for="label-restarters" class="sort-column @if( $sort_direction == 'ASC' && $sort_column == 'restarters' ) sort-column-asc @endif">
                @lang('groups.groups_restarters')
              </label>
            </th>

            @if( FixometerHelper::hasRole(Auth::user(), 'Administrator'))
              <th width="75" scope="col" class="text-center">
                <label for="label-created" class="sort-column @if( $sort_direction == 'ASC' && $sort_column == 'created_at' ) sort-column-asc @endif">
                  {{ __('Created At') }}
                </label>
              </th>
            @endif
          </tr>
        </thead>

        <tbody>
          @if( !$groups->isEmpty() )
            @foreach ($groups as $group)
              @include('partials.tables.row-groups')
            @endforeach
          @else
            <tr>
              <td colspan="13" align="center" class="p-3">There are no groups</td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-center">
      <nav aria-label="Page navigation example">
        @if (!empty($_GET))
          {!! $groups->appends(request()->input())->links() !!}
        @else
          {!! $groups->links() !!}
        @endif
      </nav>
    </div>
  </section>
@endif
