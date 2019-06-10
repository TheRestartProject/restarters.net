<thead>
    <tr>
      <th class="hightlighted"></th>

        @if( !isset($group_view) )
          <th class="table-cell-icon"></th>
        @endif
        <th scope="col">
            @lang('events.event_name')
        </th>
        <th scope="col" class="cell-date">@lang('events.event_date') / @lang('events.event_time')</th>
        <th scope="col" class="d-none d-sm-table-cell"><button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="top" data-content="@lang('events.stat-1')">@include('events.tables.svgs.invite')</button></th>
        <th scope="col" class="d-none d-sm-table-cell"><button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="top" data-content="@lang('events.stat-2')">@include('events.tables.svgs.restarters')</button></th>

        <th scope="col" class="d-none d-sm-table-cell"></th>
    </tr>
</thead>
