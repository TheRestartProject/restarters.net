<thead>
  <tr>
    <th width="42">
    </th>

    <th width="200" scope="col">
      @lang('groups.groups_name')
    </th>

    <th width="200" scope="col">
      @lang('groups.groups_location')
    </th>

    <th width="100" scope="col" class="text-center">
      <label for="label-upcoming_event" class="sort-column @if( $sort_column == 'upcoming_event' ) sort-column-{{{ strtolower($sort_direction) }}} @endif">
        @lang('groups.groups_upcoming_event')
      </label>
    </th>

    <th width="75" scope="col" class="text-center">
      &nbsp;
    </th>
  </tr>
</thead>
