@extends('layouts.app')

@section('content')
<div class="container-fluid" id="dashboard">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <h1>Welcome, <span class="orange"><?php echo $user->name; ?></span></h2>

        </div>


    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="party-opt-in dbObject">
                <h3>Upcoming Parties!</h3>
                <ul>
                  @if(isset($upcomingParties))
                    @foreach($upcomingParties as $e)
                    <li class="clearfix">
                        <time><?php echo dateFormatNoTime($e->event_date) . ' - FROM ' . $e->start . ' TO ' . $e->end; ?> </time>
                        <a class="location" title="<?php echo $e->location; ?>" href="http://maps.google.com/?ie=UTF8&hq=&q=<?php echo $e->location; ?>&ll=<?php echo $e->latitude; ?>,<?php echo $e->longitude; ?>&z=14" target="_blank"><i class="fa fa-map-marker"></i> <?php echo $e->location; ?></a>
                        <a class="cta" id="restarter-opt-in" data-party="<?php echo $e->idevents; ?>" href="#">OPT-IN!</a>
                        <div class="map-wrap clearfix" id="party-map-<?php echo $e->idevents; ?>" height="250px" width="100%"></div>
                    </li>
                    @endforeach
                  @endif
                </ul>

            </div>
        </div>

        <div class="col-md-7">
            <div class="dbObject">
                <canvas id="devicesYears"></canvas>
                <?php //dbga($devicesByYear); ?>
                <script>
                    var legends = {};

                    var data = {
                        labels: [ <?php /*echo implode(',', array_keys($devicesByYear[1])); */?>],
                        datasets: [
                            {
                                label: "Fixed!",
                                fillColor: "rgba(154,205, 50,0.2)",
                                strokeColor: "rgba(154,205, 50,1)",
                                pointColor: "rgba(154,205, 50,1)",
                                pointStrokeColor: "#fff",
                                pointHighlightFill: "#fff",
                                pointHighlightStroke: "rgba(154,205, 50,1)",
                                data: [<?php /*echo implode(', ', $devicesByYear[1]); */?> ]
                            },
                            {
                                label: "Repairable",
                                fillColor: "rgba(175,238,238,0.2)",
                                strokeColor: "rgba(175,238,238,1)",
                                pointColor: "rgba(175,238,238,1)",
                                pointStrokeColor: "#fff",
                                pointHighlightFill: "#fff",
                                pointHighlightStroke: "rgba(175,238,238,1)",
                                data: [<?php /*echo implode(', ', $devicesByYear[2]); */?> ]
                            },
                            {
                                label: "End Of Life",
                                fillColor: "rgba(0,0,0,0.2)",
                                strokeColor: "rgba(0,0,0,1)",
                                pointColor: "rgba(0,0,0,1)",
                                pointStrokeColor: "#fff",
                                pointHighlightFill: "#fff",
                                pointHighlightStroke: "rgba(0,0,0,1)",
                                data: [<?php /*echo implode(', ', $devicesByYear[3]); */?> ]
                            },
                        ]
                    };

                    var ctx = document.getElementById("devicesYears").getContext("2d");
                    var myLineChart = new Chart(ctx).Line(data, {
                        tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>",
                        datasetStrokeWidth : 0.5,
                        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].strokeColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>"
                    });
                    //then you just need to generate the legend
                    legends.devicesYears = myLineChart.generateLegend();



                </script>
            </div>
        </div>
        <div class="col-md-7"><div class="db-object">A Block Here</div></div>
    </div>

    <div class="row">
        <div class="col-md-2"><div class="db-object">A Block Here</div></div>
        <div class="col-md-2"><div class="db-object">A Block Here</div></div>
        <div class="col-md-8"><div class="db-object">A Block Here</div></div>
    </div>

    <div class="row">
        <div class="col-md-4"><div class="db-object">A Block Here</div></div>
        <div class="col-md-4"><div class="db-object">A Block Here</div></div>
        <div class="col-md-4"><div class="db-object">A Block Here</div></div>
    </div>
</div>
@endsection
