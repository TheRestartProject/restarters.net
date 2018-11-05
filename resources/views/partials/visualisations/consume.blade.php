<aside class="widget widget__1">
    <h2>{{{ $title }}}</h2>
    @if( $measure == 'km' )
      <p>{{{ $equal_to }}} {{{ $measure }}}</p>
    @else
      <p>{{{ $equal_to }}} {{{ str_plural($measure, $equal_to) }}}</p>
    @endif
    <br>
    @include('partials.visualisations.'.$measure.'-svg')
</aside>
