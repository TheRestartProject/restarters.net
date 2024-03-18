@include('layouts.header_plain', ['iframe' => true])

@yield('content')

  @if( $format == 'consume' )
    @include('partials.visualisations.consume')
  @elseif( $format == 'manufacture' )
    @include('partials.visualisations.manufacture')
  @elseif( $format == 'leaf' )
    @include('partials.visualisations.leaf')
  @endif
  
</body>
</html>
