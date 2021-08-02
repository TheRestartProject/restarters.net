<section class="dashboard__block">
    <div class="dashboard__block__media"><img src="/images/dashboard/dashboard__restarters_in_your_area.jpg" alt="Volunteers meeting members of the public to help with their device problems"></div>
    <div class="dashboard__block__content">
        <h4>@lang('partials.restarters_in_your_area')</h4>
        @if ( App\Helpers\Fixometer::hasRole(Auth::user(), 'Restarter') )
          <p>@lang('partials.area_text_1')</p>
        @else
          <p>@lang('partials.area_text_2')</p>
        @endif
        <div class="dashboard__links d-flex flex-row justify-content-end">
            @foreach ($all_groups as $g)
              <a href="{{ url('/') }}/group/edit/{{ $g->idgroups }}#invite">{{ $g->name }}</a>
            @endforeach
        </div>
    </div>
</section>
