<section class="dashboard__block">

    <div class="dashboard__block__header dashboard__block__header--wiki">
        <h4>Your Networks</h4>
    </div>

    <div class="dashboard__block__content dashboard__block__content--table">

        <p>@lang('partials.hot_topics_text')</p>
            <ul>
                @php( $count = 1 )
                @foreach( $user->networks as $network )
                    <li>
                        <a href="/networks/{{ $network->id }}">{{ $network->name }}</a>
                    </li>
                @endforeach
            </ul>
    </div>
</section>
