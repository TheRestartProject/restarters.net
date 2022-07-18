@php
    $color = '';
    // Mention these tags so that the translation script finds them.
    switch( $reliability ) :
        case( 1 ):
          // admin.reliability-1
          $color = '#AD2C1C';
          break;
        case( 2 ):
          // admin.reliability-2
          $color = '#FF1B00';
          break;
        case( 3 ):
          // admin.reliability-3
          $color = '#FFBA00';
          break;
        case( 4 ):
          // admin.reliability-4
          $color = '#43B136';
          break;
        case( 5 ):
          // admin.reliability-5
          $color = '#26781C';
          break;

        default:
          // admin.reliability-6
          $reliability = 6;
          $color = '#FFBA00';
          break;
    endswitch;
@endphp

<td><span class="badge indicator-{{ $reliability }}" style="background-color: {{ $color }}">@lang('admin.reliability-' . $reliability)</span></td>
