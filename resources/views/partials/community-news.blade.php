<section class="dashboard__block">

    <div class="dashboard__block__content dashboard__block__content--table">
        <h4>Community news</h4>
        <p>Donec id elit non mi porta gravida at eget mets. Vestibulum id ligula porta felis euismod semper.</p>
        <div class="table-responsive">
        <table role="table" class="table table-striped">
            <tbody>
                @foreach ($news_feed as $news_item)
                  <tr>
                      <td><a href="{{ $news_item->link }}">{{ $news_item->title }}</a></td>
                  </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="{{ url('/') }}/news">Read the news</a>
        </div>
    </div>
</section>
