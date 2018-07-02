@php
    $state = '';
    $device = '';
    switch( $status ) :
        case( 1 ):
          $state = 'danger';
          $device = 'end';
          break;
        case( 2 ):
          $state = 'warning';
          $device = 'repairable';
          break;
        case( 3 ):
          $state = 'success';
          $device = 'fixed';
          break;

        default:
          $state = 'warning';
          $device = 'n/a';
          break;
    endswitch;
@endphp

<td><span class="badge badge-{{ $state }}">@lang('devices.' . $device)</span></td>
