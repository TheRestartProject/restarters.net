<section class="dashboard__block">
    <div class="dashboard__block__media">
    <img src="/images/dashboard/dashboard__keep-your-group-info-uptodate.jpg" alt="A screenshot of our Fixometer platform"></div>
    <div class="dashboard__block__content">
      <h4>@lang('partials.information_up_to_date')</h4>
      <p>@lang('partials.information_up_to_date_text')</p>
        @foreach($outdated_groups as $outdated_group)
          <div class="dashboard__links d-flex flex-row justify-content-end">
              <a href="/group/edit/{{ $outdated_group->idgroups }}">@lang('partials.update') {{ $outdated_group->name }}</a>
          </div>
        @endforeach
    </div>
</section>
