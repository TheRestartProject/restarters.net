<section class="dashboard__block">

    <div class="dashboard__block__content dashboard__block__content--table">
      <h4>@lang('partials.community_news')</h4>
      <p>@lang('partials.community_news'_text)</p>
        <div class="table-responsive">
        <table role="table" class="table table-striped">
            <tbody>
                @foreach ($news_feed as $news_item)
                  <tr>
                      <td><a href="{{ $news_item->link }}" target="_blank" rel="noopener noreferrer">{{ $news_item->title }}</a></td>
                  </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="https://therestartproject.org/blog/" target="_blank" rel="noopener noreferrer">@lang('partials.see_more_posts')</a>
        </div>
    </div>
</section>
