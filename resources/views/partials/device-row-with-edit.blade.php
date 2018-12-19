<tr>
    @include('partials/device-comment-photo', ['comment' => $device->problem ])
    <td class="deviceID">
        <a href="/device/page-edit/<?php echo $device->id; ?>">
            {{{ $device->id }}}
        </a>
    </td>
    <td class="category">
        {{{ $device->category_name }}}
    </td>
    <td class="brand">
        {{{ $device->brand }}}
    </td>
    <td class="model">
        {{{ $device->model }}}
    </td>
    <td class="comment">
        {{{ $device->problem }}}
    </td>
    <td class="eventGroup">
        {{{ $device->group_name }}}
    </td>
    <td class="eventDate">
        {{{ strftime('%Y-%m-%d', $device->event_date) }}}
    </td>
    @include('partials/device-status', ['status' => $device->repair_status])
</tr>
