@if( session('repair_network') == 2 )
    @include('includes/logo-repairshare')
@elseif( session('repair_network') == 3 )
    @include('includes/logo-repairtogether')
@elseif( session('repair_network') == 8 )
  @include('includes/logo-mres')
@elseif( session('repair_network') == 1000 )
    @include('includes/logo-testing')
@else
    @include('includes/logo-restarters')
@endif
