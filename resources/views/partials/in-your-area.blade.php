<section class="dashboard__block">
    <img src="http://via.placeholder.com/480x200" alt="">
    <div class="dashboard__block__content">
        <h4>Restarters in your area</h4>
        <p>Donec id elit non mi porta gravida at eget mets. Vestibulum id ligula porta felis euismod semper.</p>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            @foreach ($all_groups as $g)
              <a href="{{ url('/') }}/group/edit/{{ $g->idgroups }}#invite">{{ $g->name }}</a>
            @endforeach
        </div>
    </div>
</section>
