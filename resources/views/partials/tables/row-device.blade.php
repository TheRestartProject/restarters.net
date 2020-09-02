@php
$editable = ( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) ) ) || ( is_object($is_attending) && $is_attending->status == 1 )
@endphp
<tr id="summary-{{ $device->iddevices }}">
    <td>
        <a class="collapsed row-button" data-toggle="collapse" href="#add-edit-device-{{ $device->deviceCategory->powered  ? 'powered' : 'unpowered' }}-{{ $device->iddevices }}" role="button" aria-expanded="false" aria-controls="row-1">
            @if ($editable)
                Edit
            @else
                View
            @endif
            <span class="arrow">▴</span>
        </a>
    </td>
    <td class="text-center">0</td>
    <td class="d-none d-md-table-cell"><div class="category">{{ $device->deviceCategory->name }}</div></td>
    @if ($powered)
    <td class="d-none d-md-table-cell"><div class="brand">{{ $device->brand }}</div></td>
    <td class="d-none d-md-table-cell"><div class="model">{{ $device->model }}</div></td>
    @else
    <td class="d-none d-md-table-cell"><div class="item_type">{{ $device->item_type }}</div></td>
    @endif
    <td class="d-none d-md-table-cell"><div class="age">{{ $device->age }}</div></td>
    <td width="300"><div class="problem">{!! $device->getShortProblem() !!}</div></td>
    @if ( $device->repair_status == 1 )
      <td><div class="repair_status"><span class="badge badge-success">@lang('partials.fixed')</span></div></td>
    @elseif ( $device->repair_status == 2 )
      <td><div class="repair_status"><span class="badge badge-warning">@lang('partials.repairable')</span></div></td>
    @elseif ( $device->repair_status == 3 )
      <td><div class="repair_status"><span class="badge badge-danger">@lang('partials.end')</span></div></td>
    @else
      <td><div class="repair_status"></div></td>
    @endif
    <?php /*
    @if ($device->more_time_needed == 1)
      <td><div class="repair_details">@lang('partials.more_time')</div></td>
    @elseif ($device->professional_help == 1)
      <td><div class="repair_details">@lang('partials.professional_help')</div></td>
    @elseif ($device->do_it_yourself == 1)
      <td><div class="repair_details">@lang('partials.diy')</div></td>
    @else
      <td><div class="repair_details">N/A</div></td>
    @endif*/ ?>
    <td>
      <svg @if ( $device->spare_parts == 0 || $device->spare_parts == 2 ) style="display: none;" @endif class="table-tick" width="21" height="17" viewBox="0 0 16 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;position:relative;z-index:1"><g><path d="M5.866,12.648l2.932,-2.933l-5.865,-5.866l-2.933,2.933l5.866,5.866Z" style="fill:#0394a6;"/><path d="M15.581,2.933l-2.933,-2.933l-9.715,9.715l2.933,2.933l9.715,-9.715Z" style="fill:#0394a6;"/></g></svg>
    </td>
    @if( Auth::check() && ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::userHasEditPartyPermission($device->event, Auth::user()->id) ) )
    <td class="d-none d-md-table-cell"><a data-device-id="{{{ $device->iddevices }}}" class="row-button delete-device" href="{{ url('/device/delete/'.$device->iddevices) }}"><svg width="15" height="15" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><g opacity="0.5"><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></g></svg></a></td>
    @endif
</tr>
<tr class="table-row-details">
    <td colspan="11" class="p-0">
        @include('fixometer.device-add-or-edit', [
            'device' => $device,
            'powered' => $device->deviceCategory->powered,
            'add' => FALSE,
            'edit' => TRUE
        ])
    </td>
</tr>