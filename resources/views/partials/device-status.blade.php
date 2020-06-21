@php
    $state = '';
    $device = '';
    switch( $status ) :
        case( 3 ):
          $state = 'danger';
          $device = 'end';
          break;
        case( 2 ):
          $state = 'warning';
          $device = 'repairable';
          break;
        case( 1 ):
          $state = 'success';
          $device = 'fixed';
          break;

        default:
          $state = 'secondary';
          $device = 'n/a';
          break;
    endswitch;
@endphp

<td class="repair_status" @if( !is_null($user_preferences) && !in_array('repair_status', $user_preferences) ) style="display: none;" @endif>
    <span class="badge badge-{{ $state }}">@lang('devices.' . $device)</span>
</td>
