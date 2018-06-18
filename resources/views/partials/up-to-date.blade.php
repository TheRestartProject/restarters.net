<section class="dashboard__block">
    <img src="http://via.placeholder.com/480x200" alt="">
    <div class="dashboard__block__content">
        <h4>Keep your group information up to date!</h4>
        <p>Donec id elit non mi porta gravida at eget mets. Vestibulum id ligula porta felis euismod semper.</p>
        @foreach($inactive_groups as $inactive_group)
          <div class="dashboard__links d-flex flex-row justify-content-end">
              <a href="/group/edit/{{ $inactive_group->idgroups }}">Update {{ $inactive_group->name }}</a>
          </div>
        @endforeach
    </div>
</section>
