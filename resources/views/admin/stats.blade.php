@include('layouts.header_plain', ['iframe' => true])
@yield('content')

    <div class="container" id="public-dataviz-stats">
        <?php if($section == 1){ ?>
        <?php if($paragraph_only == false ) { ?>
        <a href="https://therestartproject.org/faq" target="_top">
        <?php } ?>

        <section class="row" id="impact-header">

            <div class="col-sm-12 text-center">

                <p class="big">
                    <?php if($paragraph_only == 'yes'){ ?> <a href="https://therestartproject.org/impact" target="_top"> <?php } ?>
                    <span class="big blue"><?php echo $pax; ?> participants</span> aided by <span class="big blue"><?php echo $hours; ?> hours of volunteered time</span> worked on <span class="big blue"><?php echo ($device_count_status[0]['counter'] + $device_count_status[1]['counter'] + $device_count_status[2]['counter']) ?> devices.</span>
                    <?php if($paragraph_only == 'yes'){ ?> </a> <?php } ?>
                </p>

            </div>
        </section>
        <?php if(!$paragraph_only){ ?>
        <section class="row" id="impact-devices">
            <div class="col-md-6 col-md-offset-3  text-center">

                <div class="impact-devices-1">
                    <img src="{{ asset('/icons/impact_device_1.jpg') }}" class="" width="200">
                    <span class="title"><?php echo (int)$device_count_status[0]['counter'];?></span>
                    <span class="legend">were fixed</span>
                </div>

                <div class="impact-devices-2">
                    <img src="{{ asset('/icons/impact_device_2.jpg') }}" class="" width="200">
                    <span class="title"><?php echo (int)$device_count_status[1]['counter'];?></span>
                    <span class="legend">were still repairable</span>
                </div>

                <div class="impact-devices-3">
                    <img src="{{ asset('/icons/impact_device_3.jpg') }}" class="" width="200">
                    <span class="title"><?php echo (int)$device_count_status[2]['counter'];?></span>
                    <span class="legend">were dead</span>
                </div>

            </div>

            <div class="col-md-12">
                <h2><span class="title-text">Most Repaired Devices</span></h2>

                <div class="row">
                    <div class="col-xs-4 col-sm-4 col-md-4"><div class="topper  text-center"><?php echo $top[0]['name'] . ' [' . $top[0]['counter'] . ']'; ?></div></div>
                    <div class="col-xs-4 col-sm-4 col-md-4"><div class="topper  text-center"><?php echo $top[1]['name'] . ' [' . $top[1]['counter'] . ']'; ?></div></div>
                    <div class="col-xs-4 col-sm-4 col-md-4"><div class="topper  text-center"><?php echo $top[2]['name'] . ' [' . $top[2]['counter'] . ']'; ?></div></div>
                </div>
            </div>
            <?php if($paragraph_only == false ) { ?>
            </a>
            <?php } ?>
        </section>
        <?php  } ?>
        <?php } elseif($section == 2) { ?>


        <section class="row" id="impact-dataviz">
            <div class="col-md-12 text-center texter">
                <span class="datalabel">Total waste prevented:</span><span class="blue">  <?php echo number_format(round($wasteTotal), 0, '.', ','); ?> kg </span>
            </div>
            <div class="col-md-12 text-center texter">
                <span class="datalabel">Total CO<sub>2</sub> emission prevented:</span><span class="blue"><?php echo number_format(round($co2Total), 0, '.', ','); ?> kg</span>
            </div>
            <div class="col-md-12">
                <?php
                /** find size of needed SVGs **/
                if($co2Total > 6000) {
                    $consume_class = 'car';
                    $consume_image = 'Counters_C2_Driving.svg';
                    $consume_label = 'Equal to driving';
                    $consume_eql_to = (1 / 0.12) * $co2Total;
                    $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',') . '<small>km</small>';

                    $manufacture_eql_to = round($co2Total / 6000);
                    $manufacture_img = 'Icons_04_Assembly_Line.svg';
                    $manufacture_label = 'or like the manufacture of <span class="dark">' . $manufacture_eql_to . '</span> cars';
                    $manufacture_legend = ' 6000kg of CO<sub>2</sub>';
                }
                else {
                    $consume_class = 'tv';
                    $consume_image = 'Counters_C1_TV.svg';
                    $consume_label = 'Like watching TV for';
                    $consume_eql_to = ((1 / 0.024) * $co2Total) / 24;
                    $consume_eql_to = number_format(round($consume_eql_to), 0, '.', ',') . '<small>days</small>';

                    $manufacture_eql_to = round($co2Total / 100);
                    $manufacture_img = 'Icons_03_Sofa.svg';
                    $manufacture_label = 'or like the manufacture of <span class="dark">' . $manufacture_eql_to . '</span> sofas';
                    $manufacture_legend = ' 100kg of CO<sub>2</sub>';
                }
                ?>

                <div class="di_consume <?php echo $consume_class; ?>">
                    <img src="{{ asset('/icons/'.$consume_image) }}" class="img-responsive">
                    <div class="text">
                        <div class="blue"><?php echo $consume_label; ?></div>
                        <div class="consume"><?php echo $consume_eql_to; ?></div>
                    </div>
                </div>

                <div class="di_manufacture">
                    <div class="row">
                    <div class="col-md-12 text-center"><div class="lightblue"><?php echo $manufacture_label; ?></div></div>
                    </div>
                    <div class="row">
                    <?php for($i = 1; $i<= $manufacture_eql_to; $i++){ ?>
                        <div class="col-xs-4 col-sm-4 col-md-3 text-center">
                            <img src="{{ asset('/icons/'.$manufacture_img) }}" class="img-responsive">
                        </div>
                    <?php } ?>
                   </div>
                    <div class="row">
                        <div class="col-md-12 text-center clearfix">
                            <br /><br /><br />
                            <div class="legend">1 <img src="{{ asset('/icons/'.$manufacture_img) }}"> = <?php echo $manufacture_legend; ?> (approximately)</div>

                        </div>
                    </div>
                </div>


            </div>

        </section>
        <?php } ?>
    </div>

@include('layouts.footer')
