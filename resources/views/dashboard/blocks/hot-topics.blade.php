<section class="dashboard__block">

    <div class="dashboard__block__header dashboard__block__header--hottopics">
        <h4>@lang('partials.hot_topics')</h4>
    </div>

    <div class="dashboard__block__content dashboard__block__content--table">

        <p>@lang('partials.hot_topics_text')</p>
            <ol class="list-unstyled dashboard__list-topics">

                @php( $count = 1 )
                @foreach( $hot_topics['talk_hot_topics'] as $hot_topic )
                    <li class="category1" @if( isset($hot_topics['talk_categories'][$hot_topic->category_id]) ) style="border-color: #{{{ $hot_topics['talk_categories'][$hot_topic->category_id]->color }}};" @endif>
                        <a @if( isset($hot_topics['talk_categories'][$hot_topic->category_id]) ) title="From category '{{{ $hot_topics['talk_categories'][$hot_topic->category_id]->name }}}'" @endif href="{{{ env('DISCOURSE_URL') }}}/t/{{{ $hot_topic->slug }}}/{{{ $hot_topic->id }}}" target="_blank">
                            <span class="digit">{{{ $count }}}</span>
                            @if( strtotime($hot_topic->created_at) > strtotime('-5 days') )
                                <span class="badge badge-danger">New !</span>
                            @endif
                            <span class="topic-label">{{{ $hot_topic->title }}}</span>
                        </a>
                    </li>
                    @php( $count++ )
                @endforeach
            </ol>
            <div class="dashboard__links d-flex flex-row justify-content-end">
                <a href="https://talk.restarters.net/top/weekly">@lang('partials.hot_topics_link')</a>
            </div>
    </div>
</section>
