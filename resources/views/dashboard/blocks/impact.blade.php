<section class="dashboard__block dashboard__block mb-20">
    <div class="dashboard__block__content dashboard__block__content--background-white">

        <div class="d-flex flex-column flex-sm-row justify-content-start">
            <div class="col-sm-3 col-xl-2 statborder">
                <h4>@lang('partials.our_global_environmental_impact')</h4>

                <p>@lang('partials.the_impact_of_restarters')</p>
            </div>
            <div class="col-sm-3 col-xl-2 statbox statborder d-flex flex-column justify-content-start align-items-center">
                <div style="height:52px; margin:10px" class="d-flex align-items-center">
                    <img style="width:44px" src="/images/dashboard/bin.png" />
                </div>
                <div class="statfigure text-center">{{ number_format($impact_stats[0]->total_weights,0,",",",") }} kg</div>
                <h3>@lang('partials.waste_prevented')</h3>
            </div>
            <div class="col-sm-3 col-xl-2 statbox statborder d-flex flex-column align-items-center justify-content-start">
                    <div style="height:52px; margin:10px" class="d-flex align-items-center">
                    <img style="width:58px" src="/images/dashboard/co2.png" />
                </div>
                <div class="statfigure text-center">{{ number_format($impact_stats[0]->total_footprints,0,",",",") }} kg</div>
                <h3>@lang('partials.co2')</h3>
            </div>
            <div class="col-sm-2 d-flex flex-column justify-content-center align-items-center">
                <div class="dashboard__links d-flex flex-column justify-content-center align-items-center">
                    <a href="https://therestartproject.org/impact" target="_blank" rel="noopener noreferrer">@lang('partials.more_info')</a>
                </div>
            </div>
        </div>

    </div>

</section>
