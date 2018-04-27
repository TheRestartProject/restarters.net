<div class="container-fluid" id="homepage-contents">
    
    <div class="row">
        <div class="col-md-3">
            <div class="widget" id="groups">
                <h3>Restart Groups</h3>
                <ul>
                    <?php foreach($groups as $g){ ?>
                    <li>
                        <h4><?php echo $g->name; ?></h4>
                        <p></p>
                    </li>
                    
                    <?php } ?>
                </ul>
            </div>
            
        </div>
        <div class="col-md-9">
            <div id="groupWorldMap" width="100%" style="height: 400px">
                
            </div>
        </div>
        
    </div>
    
    <div class="row">
        <div class="col-md-3">
            <div class="widget" id="parties">
                <h3>Latest Parties</h3>
                    <ul>
                    <?php foreach($parties as $p){ ?>
                    
                        <li>
                            <time datetime="DD/MM/YYYY"><?php echo strftime('%d/%m/%Y', $p->event_date); ?></time>
                            <h4><?php echo $p->location; ?></h4>
                            <p></p>
                        </li>
                    
                    <?php } ?>
                    
                    </ul>
                
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="widget">
                <h3>Devices Handled: <?php echo array_sum($devices); ?></h3>
                <div class="chart-wrap">
                    <canvas id="devicesHandled" width="300" height="300"></canvas>
                    
                    <script>
                        var data = [
                            {
                                value: <?php echo $devices['fixed']; ?>,
                                color: 'yellowgreen',
                                hightlight: 'grassgreen',
                                label: 'Fixed'
                            },
                            {
                                value: <?php echo $devices['fixed']; ?>,
                                color: 'paleturquoise',
                                hightlight: 'turquoise',
                                label: 'Repairable'
                            },
                            {
                                value: <?php echo $devices['dead']; ?>,
                                color: 'black',
                                hightlight: '#CCC',
                                label: 'End of Life'
                            }
                        ];
                        
                        
                        var pieChartCtx = document.getElementById('devicesHandled').getContext('2d');
                        var pieChart = new Chart(pieChartCtx).Doughnut(data);
                    </script>
                </div>
                
                
            </div>
        </div>
        <div class="col-md-3">
            <div class="widget">
                <h3>Devices By Category (Top 10)</h3>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="widget">
                <h3>CO<sub>2</sub> Emission Prevented</h3>
            </div>
        </div>
    </div>
    <!-- eof row -->
</div>
