<section class="dashboard__block">

    <div class="dashboard__block__content dashboard__block__content--table">
      <h4>Community news</h4>
      <p>The latest from our community blog - we are always looking for guest posts, send ideas to <a href="mailto:janet@therestartproject.org">janet@therestartproject.org</a></p>
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
            <a href="https://therestartproject.org/community/" target="_blank" rel="noopener noreferrer">See more posts</a>
        </div>
    </div>
</section>
