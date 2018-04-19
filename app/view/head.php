<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GA_TRACKING_ID; ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '<?php echo GA_TRACKING_ID; ?>');
        </script>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?php echo $title; ?> | Fix-O-Meter</title>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,300italic,400|Patua+One">
        <!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> -->
        <link rel="stylesheet" href="/dist/css/main.css">
        <link rel="stylesheet" href="/components/fontawesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="/components/bootstrap-select/dist/css/bootstrap-select.min.css">
        <link rel="stylesheet" href="/components/bootstrap-sortable/Contents/bootstrap-sortable.css">
        <link rel="stylesheet" href="/components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css">
        <link rel="stylesheet" href="/components/summernote/dist/summernote.css">
        <link rel="stylesheet" href="/components/bootstrap-fileinput/css/fileinput.min.css">
        <?php
        if(isset($css)){
            foreach($css as $script){
        ?>
        <link rel="stylesheet" href="<?php echo $script; ?>">
        <?php
            }
        }
        ?>
        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

        <!-- jQ needed here -->
        <script src="/components/jquery/dist/jquery.min.js"></script>
        <script src="/components/moment/min/moment.min.js"></script>
        <script src="/components/bootstrap/dist/js/bootstrap.min.js"></script>
        <?php
        if(isset($gmaps) && $gmaps == true) {
        ?>
        <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&signed_in=true"></script>
        <?php
        }
        ?>
        <?php
        if(isset($js) && isset($js['head']) && !empty($js['head'])){
            foreach($js['head'] as $script){
        ?>
        <script src="<?php echo $script; ?>"></script>
        <?php
            }
        }
        ?>
        <script type="text/javascript">
            var feature__device_photos = <?php var_export(featureIsEnabled(FEATURE__DEVICE_PHOTOS)); ?>;
            var feature__device_age = <?php var_export(featureIsEnabled(FEATURE__DEVICE_AGE)); ?>;
        </script>


    <?php
    if($charts) {
    ?>
    <script src="/components/Chart.js/Chart.min.js"></script>
    <script>
        // MAIN CHART CONFIG
        Chart.defaults.global.responsive = true;
    </script>
    <?php
    }
    ?>
    </head>
    <body>
