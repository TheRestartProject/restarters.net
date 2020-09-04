<div class="table-responsive">
    <table class="table table-repair" role="table" id="device-table-{{ $powered ? 'powered' : 'unpowered' }}">
        <thead>
        <tr>
            <th width="60"></th>
            <th class="d-none d-md-table-cell">Category</th>
            <th class="d-none d-md-table-cell">@lang('devices.brand')</th>
            @if ($powered)
            <th class="d-none d-md-table-cell">Model</th>
            @endif
            <th class="d-none d-md-table-cell">Age</th>
            <th><span class="d-none d-sm-inline">Description of problem/solution</span></th>
            <th width="65px">Status</th>
            <th width="95px">Spare parts</th>
            @if( Auth::check() )
                @if(FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) )
                    <th width="35px" class="d-none d-md-table-cell"></th>
                @endif
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($event->devices as $device)
            @if ($powered && $device->deviceCategory->powered || !$powered && !$device->deviceCategory->powered)
                @include('partials.tables.row-device')
            @endif
        @endforeach
        </tbody>
    </table>
</div>
