@extends('layouts.app')

@section('extra-css')

    <style>
     body {
         text-align: center !important;
     }

     h1 {
         font-family: 'Asap' !important;
         font-weight: bold !important;
         float: left; /* bootstrap pull-left class working locally but not on server!  falling back on this. */
     }

     img#faultcat {
         width: 48px;
         height: 48px;
         float: right; /* bootstrap pull-right class working locally but not on server!  falling back on this. */
     }


     .title {
         font-weight: bold;
     }

     .is-horizontal-center {
         justify-content: center;
     }

     .hide {
         display: none;
     }

     .show {
         display: block;
     }

     .underline {
         text-decoration: underline;
     }

     .options {
         margin-top: 15px;
     }

     .problem {
         font-size: 1rem;
         background-color: #f5f5f5;
         border: 5px solid #FFDD57;
         border-radius: 5px;
     }

     .tag {
         margin-bottom: 2px;
     }

     .faultcat .btn {
         font-family: 'Open Sans';
     }

     .btn-fault-option-current {
         background-color: #bdbdbd !important;
     }

     #btn-translate a {
         color: white;
         text-decoration: underline;
     }

     #Y,
     #N,
     #fetch,
     #change {
         margin-bottom: 2px;
     }

    </style>

@endsection

@section('content')

<section class="faultcat">
    <div class="container mt-1 mt-sm-4">
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">FaultCat</h1>
            </div>
            <div class="col-6">
                <!--
            These images are licensed under the Creative Commons Attribution 4.0 International license.
            Attribution: Vincent Le Moign
            https://commons.wikimedia.org/wiki/Category:SVG_emoji_smilies
            -->
                <?php
            if ($fault) {
                switch ($fault->repair_status) {
                    case 'Fixed': $img = '099-smiling-cat-face-with-heart-eyes-64px.svg.png';
                        $alt = 'LoveCat';
                        break;
                    case 'Repairable': $img = '097-grinning-cat-face-with-smiling-eyes-64px.svg.png';
                        $alt = 'HappyCat';
                        break;
                    case 'End of life': $img = '103-crying-cat-face-64px.svg.png';
                        $alt = 'SadCat';
                        break;
                    case 'Unknown': $img = '102-weary-cat-face-64px.svg.png';
                        $alt = 'HuhCat';
                        break;
                }
            } else {
                $img = '100-cat-face-with-wry-smile.svg.png';
                $alt = 'MehCat';
            }
            ?>
                <img id="faultcat" class="pull-right" src="{{ asset('/images/faultcat/'.$img) }}" alt="{{ $alt }}" width="48" height="48" />
            </div>
        </div>
        <?php if ($fault) { ?>
        <div class="row problem p-2 mb-2 mx-1 mx-sm-0 notification">
            <div class="col">
                <div class="row">
                    <div class="col">
                        <p>
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info"><?php echo $fault->category; ?></span>
                            <?php
                        if (!empty($fault->brand) && $fault->brand !== 'Unknown') {
                            ?>
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info"><?php echo $fault->brand; ?></span>
                            <?php
                        }
                        if (!empty($fault->model) && $fault->model !== 'Unknown') {
                            ?>
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info"><?php echo $fault->model; ?></span>
                            <?php
                        }
                        if ($fault->repair_status !== 'Unknown') {
                            ?>
                            <span class="btn btn-md py-1 py-sm-2 btn-fault-info"><?php echo $fault->repair_status; ?></span>
                            <?php
                        }
                        ?>
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8 offset-sm-2">
                        <p class="subtitle">
                            <?php echo $fault->problem; ?>
                        </p>
                    </div>
                    <div class="col-4 col-sm-2">
                        <button id="btn-translate" class="pull-right btn btn-fault-option btn-sm px-3 py-1">
                            <a href="https://translate.google.com/#view=home&op=translate&sl=auto&tl=en&text=<?php echo $fault->translate; ?>" target="_blank">
                                Translate
                            </a>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <form id="log-task" action="faultcat" method="POST">
            @csrf
            <div class="container fault-type">
                <div class="container">
                    <input type="hidden" id="iddevices" name="iddevices" value="<?php echo $fault->iddevices; ?>">
                    <input type="hidden" id="fault_type" name="fault_type" value="<?php echo $fault->fault_type; ?>">
                    <p class="title">Is the fault type <span id="fault-type-cur" class="btn btn-md btn-fault-info"><?php echo $fault->fault_type; ?></span>?</p>
                    <p class="buttons is-centered">
                        <button class="btn btn-md btn-success btn-rounded" id="Y">
                            <span class="underline">Y</span>es/probably/possibly
                        </button>
                        <button type="submit" name="fetch" id="fetch" class="btn btn-md btn-warning btn-rounded">
                            <span class="">I don't know,&nbsp;<span class="underline">F</span>etch another one</span>
                        </button>
                        <button type="submit" name="fetch" id="N" class="btn btn-md btn-danger btn-rounded">
                            <span class=""><span class="underline">N</span>ope, let me pick something else</span>
                        </button>
                    </p>
                </div>
                <div class="container options hide">
                    <p class="confirm hide">
                        <button class="btn-md btn-info btn-rounded" id="change"><span class="underline">G</span>o with "<span id="fault-type-new"></span>"</button>
                    </p>
                    <div class="buttons">
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Boot' ? 'btn-fault-option-current' : ''); ?>">Boot</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Case/chassis' ? 'btn-fault-option-current' : ''); ?>">Case/chassis</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Configuration' ? 'btn-fault-option-current' : ''); ?>">Configuration</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Integrated keyboard' ? 'btn-fault-option-current' : ''); ?>">Integrated keyboard</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Integrated media component' ? 'btn-fault-option-current' : ''); ?>">Integrated media component</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Integrated optical drive' ? 'btn-fault-option-current' : ''); ?>">Integrated optical drive</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Integrated pointing device' ? 'btn-fault-option-current' : ''); ?>">Integrated pointing device</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Integrated screen' ? 'btn-fault-option-current' : ''); ?>">Integrated screen</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Internal damage' ? 'btn-fault-option-current' : ''); ?>">Internal damage</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Internal storage' ? 'btn-fault-option-current' : ''); ?>">Internal storage</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Multiple' ? 'btn-fault-option-current' : ''); ?>">Multiple</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Operating system' ? 'btn-fault-option-current' : ''); ?>">Operating system</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Other' ? 'btn-fault-option-current' : ''); ?>">Other</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Overheating' ? 'btn-fault-option-current' : ''); ?>">Overheating</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Performance' ? 'btn-fault-option-current' : ''); ?>">Performance</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Ports/slots/connectors' ? 'btn-fault-option-current' : ''); ?>">Ports/slots/connectors</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Power/battery' ? 'btn-fault-option-current' : ''); ?>">Power/battery</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'System board' ? 'btn-fault-option-current' : ''); ?>">System board</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Unknown' ? 'btn-fault-option-current' : ''); ?>">Unknown</button>
                        <button class="btn btn-sm btn-fault-option btn-rounded <?php echo ($fault->fault_type == 'Virus/malware' ? 'btn-fault-option-current' : ''); ?>">Virus/malware</button>
                    </div>
                </div>
            </div>
        </form>
        <?php } ?>

        @include('faultcat/info-modal')
</section>

@endsection

@section('scripts')
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        document.getElementById('Y').addEventListener('click', function(e) {
            e.preventDefault();
            doYes();
        }, false);

        document.getElementById('N').addEventListener('click', function(e) {
            e.preventDefault();
            doNo();
        }, false);

        document.getElementById('change').addEventListener('click', function(e) {
            e.preventDefault();
            doChange();
        }, false);

        [...document.querySelectorAll('.fault-type .buttons .btn-fault-option')].forEach(elem => {
            elem.addEventListener('click', function(e) {
                e.preventDefault();
                doOption(e);
            });
        });

        document.querySelector('.fault-type .buttons .btn-fault-option-current').disabled = true;

        document.addEventListener("keypress", function(e) {
            if (e.code == 'KeyF') {
                e.preventDefault();
                document.getElementById('fetch').click();
            } else if (e.code == 'KeyY') {
                e.preventDefault();
                doYes();
            } else if (e.code == 'KeyN') {
                e.preventDefault();
                doNo();
            } else if (e.code == 'KeyG') {
                e.preventDefault();
                doChange();
            }
        }, false);

        function doYes(e) {
            submitForm();
        }

        function doNo(e) {
            document.querySelector('.options').classList.replace('hide', 'show');
        }

        function doChange(e) {
            document.querySelector('#fault_type').value = document.querySelector('#fault-type-new').innerText;
            submitForm();
        }

        function doOption(e) {
            document.querySelector('.fault-type .buttons .btn-fault-option-current').classList.toggle('btn-fault-option-current');
            e.target.classList.toggle('btn-fault-option-current');
            document.querySelector('.confirm').classList.replace('hide', 'show');
            document.querySelector('#fault-type-new').innerText = e.target.innerText;
            document.querySelector('#change').focus({
                preventScroll: false
            });
        }

        function submitForm() {
            console.log('submitForm - iddevices: ' +
                document.querySelector('#iddevices').value +
                ' / fault_type: ' +
                document.querySelector('#fault_type').value);
            document.forms['log-task'].submit();
        }

    }, false);
</script>
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        /*document.querySelector('.dropdown-trigger').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('user').classList.toggle('is-active');
        });

        document.getElementById('user').addEventListener('mouseleave', e => {
            document.getElementById('user').classList.remove('is-active');
        });*/

        /*document.getElementById('btn-info-open').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('modal-info').classList.add('is-active');
        }, false);*/

        /*document.getElementById('btn-info-close').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('modal-info').classList.remove('is-active');
        }, false);*/

        /*document.addEventListener("keypress", function(e) {
            if (e.code == 'KeyI') {
                e.preventDefault();
                document.getElementById('btn-info-open').click();
            } else if (e.code == 'KeyU') {
                e.preventDefault();
                document.querySelector('.dropdown-trigger').click();
            }
        }, false);*/

    }, false);
</script>

@endsection
