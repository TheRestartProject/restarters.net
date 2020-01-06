<div class="row row-expanded" id="logostats-header">
    <div class="col-lg-4">
        <header>
          <a href="/">
            @include('includes.logo')
          </a>
        </header>
    </div>
    <div class="col-lg-8 d-none d-md-block">
        @if (!$agent->isPhone())
        <div class="row row-compressed stats float-right text-center">
            <div class="stats__stat">
                <div class="stat-figure">{{ number_format($deviceCount, 0, '.', ',') }}</div>
                <div class="stat-header">@lang('login.stat_1')</div>
            </div>
            <div class="stats__stat">
                <div class="stat-figure">{{ number_format(round($co2Total), 0, '.', ',') }} kg</div>
                <div class="stat-header">@lang('login.stat_2')</div>
            </div>
            <div class="stats__stat">
                <div class="stat-figure">{{ number_format(round($wasteTotal), 0, '.', ',') }} kg</div>
                <div class="stat-header">@lang('login.stat_3')</div>
            </div>
            <div class="stats__stat">
                <div class="stat-figure">{{ number_format($partiesCount, 0, '.', ',') }}</div>
                <div class="stat-header">@lang('login.stat_4')</div>
            </div>
        </div>
        @endif
    </div>
</div>
