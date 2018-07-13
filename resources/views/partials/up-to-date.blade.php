<section class="dashboard__block">
    <div class="dashboard__block__media">
    <img src="/images/dashboard/dashboard__keep-your-group-info-uptodate.jpg" alt="A screenshot of our Fixometer platform"></div>
    <div class="dashboard__block__content">
      <h4>Keep your group information up to date!</h4>
      <p>A fresh profile helps recruit more volunteers. Make sure to link to your website or social media channels.</p>
        @foreach($outdated_groups as $outdated_group)
          <div class="dashboard__links d-flex flex-row justify-content-end">
              <a href="/group/edit/{{ $outdated_group->idgroups }}">Update {{ $outdated_group->name }}</a>
          </div>
        @endforeach
    </div>
</section>
