<section class="dashboard__block">
    <img src="/images/dashboard/dashboard__restarters_in_your_area.jpg" alt="Volunteers meeting members of the public to help with their device problems">
    <div class="dashboard__block__content">
        <h4>Restarters in your area</h4>
        @if ( FixometerHelper::hasRole(Auth::user(), 'Restarter') )
          <p>Through this community, potential volunteers self-register and share their location. The platform is designed to connect organisers and fixers.</p>
        @else
          <p>Through this community, potential volunteers self-register and share their location. Here's a list of potential volunteers near you</p>
        @endif
        <div class="dashboard__links d-flex flex-row justify-content-end">
            @foreach ($all_groups as $g)
              <a href="{{ url('/') }}/group/edit/{{ $g->idgroups }}#invite">{{ $g->name }}</a>
            @endforeach
        </div>
    </div>
</section>
