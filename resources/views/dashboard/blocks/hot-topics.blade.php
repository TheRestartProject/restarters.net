<section class="dashboard__block">

    <div class="dashboard__block__header dashboard__block__header--hottopics">
        <h4>@lang('partials.hot_topics')</h4>
    </div>

    <div class="dashboard__block__content dashboard__block__content--table">

        <p>@lang('partials.hot_topics_text')</p>
            <ol class="list-unstyled dashboard__list-topics">
                @php( $count = 1 )
                @foreach( $hot_topics['talk_hot_topics'] as $hot_topic )
                    <li @if( isset($hot_topics['talk_categories'][$hot_topic->category_id]) ) style="border-color: #{{{ $hot_topics['talk_categories'][$hot_topic->category_id]->color }}}; border-bottom:1px solid #eee" @endif>
                        <span class="hottopic" >
                            @if( strtotime($hot_topic->created_at) > strtotime('-4 days') )
                                <span class="badge badge-danger">NEW!</span>
                            @endif
                            <span class="topic-label"><a href="{{{ env('DISCOURSE_URL') }}}/session/sso?return_path={{{ env('DISCOURSE_URL') }}}/t/{{{ $hot_topic->slug }}}/{{{ $hot_topic->id }}}">{{{ $hot_topic->title }}}</a></span>
                            @if( isset($hot_topics['talk_categories'][$hot_topic->category_id]) ) 
                            <div><span style="display:inline-block; width:9px; height:9px; margin-right:5px; background-color: #{{{ $hot_topics['talk_categories'][$hot_topic->category_id]->color }}};"></span><span style="font-size:.8706em; font-weight:bold" >{{{ $hot_topics['talk_categories'][$hot_topic->category_id]->name }}}</span></div>
                            @endif
                        </span>
                    </li>
                    @if ($count > 4) @break @endif
                    @php( $count++ )
                @endforeach
            </ol>
            <div class="dashboard__links d-flex flex-row justify-content-end">
                <a href="{{{ env('DISCOURSE_URL')}}}/session/sso?return_path=https://talk.restarters.net/top/weekly">@lang('partials.hot_topics_link')</a>
            </div>
    </div>
</section>
