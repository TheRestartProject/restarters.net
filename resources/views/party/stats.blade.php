@include('layouts.header_nocookie', ['iframe' => true])
@yield('content')
<div class="" id="party-headline-stats">

    <div class="stat1">
        <span>
                <img style="height:40px" class="" alt="Volunteers" src="{{ asset('/icons/icon_pax.png') }}">
                <span class="subtext">participants</span>
        </span>
        <?php echo $party['participants']; ?>
    </div>

    <div class="stat1">
        <span>
                <img class="" alt="The Restart Project: Logo" src="{{ asset('/images/logo_mini.png') }}">
                <span class="subtext">restarters</span>
            </span>
                <?php echo $party['volunteers']; ?>
    </div>

    <div class="stat1">
        <div class="footprint">
            <div style="line-height:10px;margin-bottom:10px;">
            <span id="co2-diverted-value"><?php echo number_format(round($party['co2_total']), 0); ?></span>
            <span class="subtext">kg of CO<sub>2</sub></span>
            </div>
            <div style="line-height:10px">
            <span id="ewaste-diverted-value"><?php echo number_format(round($party['waste_total']), 0); ?></span>
            <span class="subtext">kg of waste</span>
            </div>
        </div>
    </div>

    <div class="stat1">
            <i class="status-inline mid fixed"></i>
            <span class="fixed"><?php echo $party['fixed_devices']; ?></span>
    </div>

    <div class="stat1">
            <i class="status-inline mid repairable"></i>
            <span class="repairable"><?php echo $party['repairable_devices']; ?></span>
    </div>

    <div class="stat1">
            <i class="status-inline mid dead"></i>
            <span class="dead"><?php echo $party['dead_devices']; ?></span>
    </div>

</div>
@include('layouts.footer')
