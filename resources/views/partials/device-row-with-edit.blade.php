@php( $user_preferences = session('column_preferences') )
<tr>
    @include('partials/device-comment-photo', ['comment' => $device->problem ])
    <td class="deviceID">
        <a href="/device/page-edit/<?php echo $device->id; ?>">
            {{{ $device->iddevices }}}
        </a>
    </td>
    <td class="category" @if( !FixometerHelper::checkColumn('category', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->deviceCategory->name }}}
    </td>
    <td class="brand" @if( !FixometerHelper::checkColumn('brand', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->brand }}}
    </td>
    <td class="model" @if( !FixometerHelper::checkColumn('model', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->model }}}
    </td>
    <td class="problem" @if( !FixometerHelper::checkColumn('problem', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->getShortProblem() }}}
    </td>
    <td class="group_name" @if( !FixometerHelper::checkColumn('group_name', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->deviceEvent->theGroup->name }}}
    </td>
    <td class="event_date" @if( !FixometerHelper::checkColumn('event_date', $user_preferences) ) style="display: none;" @endif>
        {{{ $device->deviceEvent->getEventDate() }}}
    </td>
    @include('partials/device-status', ['status' => $device->repair_status])
</tr>
