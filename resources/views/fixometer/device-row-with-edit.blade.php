@php( $user_preferences = session('column_preferences') )
<tr>
    @if( !App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') )
        @include('partials/device-comment-photo', ['comment' => $device->problem ])
    @endif
    <td class="pl-0 deviceID">
        <a href="/device/page-edit/{{{ $device->iddevices }}}">
            {{{ $device->iddevices }}}
        </a>
    </td>
    <td class="category" @if( !App\Helpers\Fixometer::checkColumn('category', $user_preferences) ) style="display: none;" @endif>
        @lang($device->deviceCategory->name)
    </td>
    @if ($powered)
        <td class="brand d-none d-md-table-cell" @if( !App\Helpers\Fixometer::checkColumn('brand', $user_preferences) ) style="display: none;" @endif>
            {{{ $device->brand }}}
        </td>
        <td class="model d-none d-md-table-cell" @if( !App\Helpers\Fixometer::checkColumn('model', $user_preferences) ) style="display: none;" @endif>
            {{{ $device->model }}}
        </td>
    @else
        <td class="item_type d-none d-md-table-cell" @if( !App\Helpers\Fixometer::checkColumn('item_type', $user_preferences) ) style="display: none;" @endif>
            {{{ $device->item_type }}}
        </td>
    @endif
    <td class="problem d-none d-md-table-cell" @if( !App\Helpers\Fixometer::checkColumn('problem', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->getShortProblem() }}}
    </td>
    <td class="group_name d-none d-md-table-cell" @if( !App\Helpers\Fixometer::checkColumn('group_name', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->deviceEvent->theGroup->name }}}
    </td>
    <td class="event_date d-none d-md-table-cell" @if( !App\Helpers\Fixometer::checkColumn('event_date', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->deviceEvent->getEventDate() }}}
    </td>
    @include('partials/device-status', ['status' => $device->repair_status])
</tr>
