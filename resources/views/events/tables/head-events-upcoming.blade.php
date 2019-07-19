<thead>
    <tr>
        <th scope="col"></th>
        @if( !isset($group_view) )
          <th class="table-cell-icon"></th>
        @endif
        <th scope="col">
            @lang('events.event_name')
        </th>
        <th scope="col" class="cell-date">@lang('events.event_date') / @lang('events.event_time')</th>
        @if( !isset($group_view) )
          <th scope="col" class="cell-locations d-none d-sm-table-cell">@lang('events.event_location')</th>
        @endif
        @if( !isset($hide_invite) )
            <th scope="col" class="d-none d-sm-table-cell"><button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="top" data-content="@lang('events.stat-1')">@include('events.tables.svgs.invite')</button></th>
        @endif
        <th scope="col" class="d-none d-sm-table-cell"><button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="top" data-content="@lang('events.stat-2')">@include('events.tables.svgs.restarters')</button></th>
        <th scope="col" class="d-none d-sm-table-cell"></th>
    </tr>
</thead>
