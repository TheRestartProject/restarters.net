<aside class="widget widget__1">
    <h2>{{{ $title }}}</h2>
    @if( $measure == 'km' )
      <p>{{{ $equal_to }}} {{{ $measure }}}</p>
    @else
      <p>{{{ $equal_to }}} {{{ Str::plural($measure, floatval($equal_to)) }}}</p>
    @endif
    <br>
    <div class="p-5">@include('partials.visualisations.'.$measure.'-svg')</div>
</aside>
