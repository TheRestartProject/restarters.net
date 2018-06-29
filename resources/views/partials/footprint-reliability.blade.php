@php
    $color = '';
    switch( $reliability ) :
        case( 1 ):
          $color = '#AD2C1C';
          break;
        case( 2 ):
          $color = '#FF1B00';
          break;
        case( 3 ):
          $color = '#FFBA00';
          break;
        case( 4 ):
          $color = '#43B136';
          break;
        case( 5 ):
          $color = '#26781C';
          break;
    endswitch;
@endphp

<td><span class="badge indicator-<?php echo $reliability; ?>" style="background-color: {{ $color }}">@lang('admin.reliability-' . $reliability)</span></td>
