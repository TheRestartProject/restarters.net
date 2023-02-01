<aside class="widget widget__2">
    <h2>{{{ $title }}}</h2>
    <p title="{{{ $co2 }}} kg of CO2">{{{ $equal_to }}} {{{ Str::plural($measure, floatval($equal_to)) }}}</p>
    <br>
    <div class="row row-compressed">

        @php( $item = 1 )

        @while( $item <= 9 )

          <div class="col-4 flex-column widget__item">
            @if( $item <= $equal_to )
              @include('partials.visualisations.'.Str::slug($measure).'-svg')
            @else
              @include('partials.visualisations.'.Str::slug($measure).'-overlay-svg')
            @endif
          </div>

          @php( $item++ )

        @endwhile

    </div>

    <div class="widget__summary">
      @if ( $measure == 'car' )
        <p>1 @include('partials.visualisations.car-svg') = 6000kg of CO<sub>2</sub> (approximately)</p>
      @elseif ( $measure == 'half car' )
        <p>1 @include('partials.visualisations.half-car-svg') = 3000kg of CO<sub>2</sub> (approximately)</p>
      @elseif ( $measure == 'sofa' )
        <p>1 @include('partials.visualisations.sofa-svg') = 100kg of CO<sub>2</sub> (approximately)</p>
      @endif
    </div>
</aside>
