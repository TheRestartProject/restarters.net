<section class="dashboard__block">
    <div class="dashboard__block__header dashboard__block__header--discussion">
        <h4>@lang('dashboard.discussion_header')</h4>
    </div>
    <div class="dashboard__block__media">
        <img src="/images/dashboard/dashboard__discussion.jpg" alt="Our discussion platform">
    </div>
    <div class="dashboard__block__content">
        <h5>@lang('partials.discussion')</h5>
        <p>@lang('partials.discussion_text')</p>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="https://talk.restarters.net/t/welcome-to-the-restarters-community/8" target="_blank" rel="noopener noreferrer">Welcome to the Restarters Community</a>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="https://talk.restarters.net/t/introduce-yourself-here/44" target="_blank" rel="noopener noreferrer">Who is everyone?  Meet and greet</a>
        </div>
        @if ($show_fixfest_cta)
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="https://talk.restarters.net/t/call-out-for-fixfest-uk-unconference/238" target="_blank" rel="noopener noreferrer"><strong>NEW</strong> Develop Fixfest UK's programme</a>
        </div>
        @endif
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="https://talk.restarters.net/t/save-the-date-international-repair-day-on-20-october/338" target="_blank" rel="noopener noreferrer"><strong>NEW</strong> Save the date - International Repair Day</a>
        </div>
    </div>
</section>
