<section class="dashboard__block">

    <div class="dashboard__block__content">

        <h4>@lang('partials.our_global_environmental_impact')</h4>

        <p>@lang('partials.the_impact_of_restarters')</p>
        <div>
        <ul class="properties properties__full">
            <li>
                <div class="text-center">
                    <img src="/images/dashboard/bin.svg" style="width:44px; display:block; margin:auto " />
                    <span style="  font-family: Asap;
                          font-size: 26px;

                          font-weight: bold;

                          font-style: normal;

                          font-stretch: normal;

                          line-height: normal;

                          letter-spacing: normal;

                          text-align: left;

                                 color: #0394a6;">{{ number_format($impact_stats[0]->total_weights,0,",",",") }} kg</span>
                    <h3 style="  font-family: Asap;

                               font-size: 16px;

                               font-weight: bold;

                               font-style: normal;

                               font-stretch: normal;

                               line-height: normal;

                               letter-spacing: normal;
                               padding:0;

                               text-align: center;

                               color: #000000;" >@lang('partials.waste_prevented')</h3>
                </div>
            </li>
            <li>
                <div>
                    <h3>@lang('partials.co2')</h3>
                    {{ number_format($impact_stats[0]->total_footprints,0,",",",") }} kg
                    <svg width="20" height="12" viewBox="0 0 15 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><circle cx="2.854" cy="6.346" r="2.854" style="fill:#0394a6;"/><circle cx="11.721" cy="5.92" r="3.279" style="fill:#0394a6;"/><circle cx="7.121" cy="4.6" r="4.6" style="fill:#0394a6;"/><rect x="2.854" y="6.346" width="8.867" height="2.854" style="fill:#0394a6;"/></g></svg>
                </div>
            </li>
        </ul>
        </div>

        <div class="dashboard__links d-flex flex-row justify-content-end">
            <a href="https://therestartproject.org/impact" target="_blank" rel="noopener noreferrer">@lang('partials.more_info')</a>
        </div>
    </div>

</section>
