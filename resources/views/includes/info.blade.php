<div class="row row-expanded" id="logostats-header">
    <div class="col-md-3 offset-2 p-0">
        <header>
          <a href="/">
            @include('includes.logo')
          </a>
        </header>
    </div>
    <div class="col-md-6 d-none d-md-block p-0">
        @if (!$agent->isPhone())
        <div class="row row-compressed stats float-right text-center">
            <div class="stats__stat">
                <div class="stat-figure">{{ number_format($deviceCount, 0, '.', ',') }}</div>
                <div class="stat-header">@lang('login.stat_1')</div>
            </div>
            <div class="stats__stat">
                <div class="stat-figure">
                    @if($co2Total >= 1000)
                        {{ number_format(round($co2Total / 1000), 0, '.', ',') }} tonnes
                    @else
                        {{ number_format(round($co2Total), 0, '.', ',') }} kg
                    @endif
                </div>
                <div class="stat-header">@lang('login.stat_2')</div>
            </div>
            <div class="stats__stat">
                <div class="stat-figure">
                    @if($wasteTotal >= 1000)
                        {{ number_format(round($wasteTotal / 1000), 0, '.', ',') }} tonnes
                    @else
                        {{ number_format(round($wasteTotal), 0, '.', ',') }} kg
                    @endif
                </div>
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
