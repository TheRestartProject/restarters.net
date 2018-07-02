<section class="dashboard__block">
    <img src="/images/dashboard/dashboard__keep-your-group-info-uptodate.jpg" alt="">
    <div class="dashboard__block__content">
      <h4>Keep your group information up to date!</h4>
      <p>A fresh profile helps recruit more volunteers. Make sure to link to your website or social media channels.</p>
        @foreach($inactive_groups as $inactive_group)
          <div class="dashboard__links d-flex flex-row justify-content-end">
              <a href="/group/edit/{{ $inactive_group->idgroups }}">Update {{ $inactive_group->name }}</a>
          </div>
        @endforeach
    </div>
</section>
