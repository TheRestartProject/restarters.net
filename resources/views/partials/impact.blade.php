<section class="dashboard__block">

    <div class="dashboard__block__content">

        <h4>@lang('partials.our_global_environmental_impact')</h4>

        <p>@lang('partials.the_impact_of_restarters')</p>
        <div>
        <ul class="properties properties__full">
            <li>
                <div>
                    <h3>@lang('partials.waste_prevented')</h3>
                    {{ number_format($impact_stats[0]->total_weights,0,",",",") }} kg
                    <svg width="17" height="17" viewBox="0 0 13 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M12.15,0c0,0 -15.921,1.349 -11.313,10.348c0,0 0.59,-1.746 2.003,-3.457c0.852,-1.031 2,-2.143 3.463,-2.674c0.412,-0.149 0.696,0.435 0.094,0.727c0,0 -4.188,2.379 -4.732,6.112c0,0 1.805,1.462 3.519,1.384c1.714,-0.078 4.268,-1.078 4.707,-3.551c0.44,-2.472 1.245,-6.619 2.259,-8.889Z" style="fill:#0394a6;"/><path d="M1.147,13.369c0,0 0.157,-0.579 0.55,-2.427c0.394,-1.849 0.652,-0.132 0.652,-0.132l-0.25,2.576l-0.952,-0.017Z" style="fill:#0394a6;"/></g></svg>
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
