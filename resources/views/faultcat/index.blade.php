@extends('layouts.app')

@section('extra-css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.5/css/bulma.min.css" integrity="sha256-vK3UTo/8wHbaUn+dTQD0X6dzidqc5l7gczvH+Bnowwk=" crossorigin="anonymous" />

    <style>
     body {text-align : center !important;}
     .is-horizontal-center {justify-content: center;}
     .hide {display: none;}
     .show {display: block;}
     .underline {text-decoration: underline;}
     .options {margin-top: 15px;}
     .problem {border: 5px solid #FFDD57; }
     /*.hero-head {margin-bottom: 1%;}*/
     .tag {margin-bottom: 2px;}
     #Y, #N, #fetch, #change {margin-bottom: 2px;}
     .btn-translate {padding: 10px 0;}
     .fc-center { align-items: center;}
     .fc-margin-bottom {margin-bottom: 1%;}
     .fc-margin-top {margin-top: 1%;}
    </style>

@endsection

@section('content')

        <div class="modal" id="modal-info">
            <div class="modal-background"></div>
            <div class="modal-card">
                <header class="modal-card-head has-background-warning">
                    <p class="modal-card-title">About FaultCat</p>
                    <button id="btn-info-close" class="delete" aria-label="close"></button>
                </header>
                <section class="modal-card-body">
                    <div class="container  notification">
                        <p class="is-size-6 is-size-7-mobile is-size-7-tablet">“We had a kettle; we let it leak:<br>
                            Our not repairing made it worse.<br>
                            We haven't had any tea for a week...<br>
                            The bottom is out of the Universe.”<br>
                            ― Rudyard Kipling
                        </p>
                    </div>
    <div class="container-fluid content">
        <div class="column is-1 btn-info">
            <button id="btn-info-open" class="button is-primary">
                <svg style="width:24px;height:24px" viewBox="0 0 24 24">
                <title>About FaultCat</title>
                <path fill="#000000" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z" />
                </svg>
            </button>
        </div>
                    </div>
                </section>
                <footer class="modal-card-foot has-background-warning has-text-weight-bold">
                    <div class="container">
                        <p><a href="https://therestartproject.org/" target="_blank">The Restart Project</a></p>
                        <p>A member of the <a href="https://openrepair.org/" target="_blank">Open Repair Alliance</a></p>
                    </div>
                </footer>
            </div>
        </div>
        <div class="hero is-centered" style="margin-bottom:180px">
            <div class="hero-foot">
                <div class="container">
                    <header class="columns is-flex-mobile is-flex-tablet" style="text-align:left">
                    <div class="column is-half">
                <h1 style="font-size:2.25rem; font-weight: bold; font-family:'Asap'">FaultCat</h1>
                    </div>
                    <div class="column is-half">
                        <div  class="is-pulled-right">
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
            <img class="image is-48x48" src="{{ asset('/images/faultcat/'.$img) }}" alt="{{ $alt }}"/>
        </div>
                    </div>
                </header>
                </div>
        <?php if ($fault) { ?>
            <div class="container content problem notification">
                <div class="columns is-flex-mobile is-flex-tablet">
                    <div class="column is-12">
                        <p>
                            <span class="tag is-large has-background-grey-lighter is-size-7-mobile is-size-6-tablet"><?php echo $fault->category; ?></span>
                            <?php
                            if (!empty($fault->brand) && $fault->brand !== 'Unknown') {
                                ?>
                                <span class="tag is-large has-background-grey-lighter is-size-7-mobile is-size-6-tablet"><?php echo $fault->brand; ?></span>
                                <?php
                            }
                            if (!empty($fault->model) && $fault->model !== 'Unknown') {
                                ?>
                                <span class="tag is-large has-background-grey-lighter is-size-7-mobile is-size-6-tablet"><?php echo $fault->model; ?></span>
                                <?php
                            }
                            if ($fault->repair_status !== 'Unknown') {
                                ?>
                                <span class="tag is-large has-background-grey-lighter is-size-7-mobile is-size-6-tablet"><?php echo $fault->repair_status; ?></span>
                                <?php
                            }
                            ?>
                        </p>
                    </div>
                </div>
                <div class="columns is-flex-mobile is-flex-tablet">
                    <div class="column is-10 is-offset-1">
                        <p class="subtitle is-size-6-mobile is-size-6-tablet">
                            <span><?php echo $fault->problem; ?></span>
                        </p>
                    </div>
                    <div class="column is-1 btn-translate is-narrow-mobile">
                        <button id="btn-translate" class="button is-size-7 is-size-7-mobile has-background-grey-dark has-text-white">
                            <a href="https://translate.google.com/#view=home&op=translate&sl=auto&tl=en&text=<?php echo $fault->translate; ?>" target="_blank">
                                Translate
                            </a>
                        </button>
                    </div>
                </div>
            </div>
            <form id="log-task" action="faultcat" method="POST">
                @csrf
                <div class="container fault-type">
                    <div class="container">                        
                        <input type="hidden" id="iddevices" name="iddevices" value="<?php echo $fault->iddevices; ?>">
                        <input type="hidden" id="fault_type" name="fault_type" value="<?php echo $fault->fault_type; ?>">
                        <p class="title is-size-6-mobile is-size-6-tablet">Is the fault type <span id="fault-type-cur" class="tag is-large has-background-grey-lighter is-size-6-mobile is-size-6-tablet"><?php echo $fault->fault_type; ?></span> ?</p>
                        <p class="buttons is-centered">
                            <button class="button is-rounded is-success is-size-6-mobile is-size-6-tablet" id="Y">
                                <span class="underline">Y</span>es/probably/possibly
                            </button>
                            <button type="submit" name="fetch" id="fetch" class="button is-rounded is-warning is-size-6-mobile is-size-6-tablet">
                                <span class="">I don't know,&nbsp;<span class="underline">F</span>etch another one</span>
                            </button>
                            <button type="submit" name="fetch" id="N" class="button is-rounded is-danger is-size-6-mobile is-size-6-tablet">
                                <span class=""><span class="underline">N</span>ope, let me pick something else</span>
                            </button>
                        </p>
                    </div>
                    <div class="container options hide">
                        <p class="confirm hide">
                            <button class="button is-rounded is-primary is-size-6-mobile is-size-6-tablet" id="change"><span class="underline">G</span>o with "<span id="fault-type-new"></span>"</button>
                        </p>
                        <div class="buttons is-centered">
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Boot' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Boot</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Case/chassis' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Case/chassis</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Configuration' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Configuration</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Integrated keyboard' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Integrated keyboard</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Integrated media component' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Integrated media component</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Integrated optical drive' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Integrated optical drive</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Integrated pointing device' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Integrated pointing device</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Integrated screen' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Integrated screen</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Internal damage' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Internal damage</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Internal storage' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Internal storage</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Multiple' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Multiple</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Operating system' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Operating system</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Other' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Other</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Overheating' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Overheating</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Performance' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Performance</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Ports/slots/connectors' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Ports/slots/connectors</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Power/battery' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Power/battery</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'System board' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">System board</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Unknown' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Unknown</button>
                            <button class="button is-size-6 is-size-7-mobile is-size-7-tablet has-text-white-bis is-rounded <?php echo ($fault->fault_type == 'Virus/malware' ? 'has-background-grey-light' : 'has-background-grey-dark'); ?>">Virus/malware</button>
                        </div>
                    </div>            
                    <?php if (!$user->id && $user->clicks > 3 && !$user->country && !$user->age) { ?>
                    <div class="container">
                        <br><p class="is-size-6-mobile is-size-6-tablet">I am...</p>
                        <div class="field is-grouped is-grouped-centered is-grouped-multiline">
                        <div class="control is-size-6-mobile is-size-6-tablet">
                            <label class="radio">
                              <input type="radio" name="age" value="young" checked="">
                              young
                            </label>
                            <label class="radio">
                              <input type="radio" name="age" value="old">                              
                              old
                            </label>
                            <label class="radio">
                              <input type="radio" name="age" value="declined">
                              immortal
                            </label>
                        </div>
                        <div class="control is-size-6-mobile is-size-6-tablet">
                            <label class="radio">
                              <input type="radio" name="country" value="GBR" checked="">
                              in the UK
                            </label>
                            <label class="radio">
                              <input type="radio" name="country" value="other">                              
                              not in the UK
                            </label>
                            <label class="radio">
                              <input type="radio" name="country" value="declined">
                              everywhere
                            </label>
                        </div>
                        </div>
                    </div>
                    <?php } ?>                    
                </div>
            </form>
        <?php } ?>
            </div>
        </div>
    @endsection

    @section('scripts')
        <script>
            document.addEventListener(`DOMContentLoaded`, async () => {

                document.getElementById('Y').addEventListener('click', function (e) {
                    e.preventDefault();
                    doYes();
                }, false);

                document.getElementById('N').addEventListener('click', function (e) {
                    e.preventDefault();
                    doNo();
                }, false);

                document.getElementById('change').addEventListener('click', function (e) {
                    e.preventDefault();
                    doChange();
                }, false);

                [...document.querySelectorAll('.fault-type .buttons .has-background-grey-dark')].forEach(elem => {
                    elem.addEventListener('click', function (e) {
                        e.preventDefault();
                        doOption(e);
                    });
                });

                document.querySelector('.fault-type .buttons .has-background-grey-light').disabled = true;

                document.addEventListener("keypress", function (e) {
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
                    document.querySelector('.fault-type .buttons .has-background-grey-light').classList.replace('has-background-grey-light', 'has-background-grey-dark');
                    e.target.classList.replace('has-background-grey-dark', 'has-background-grey-light');
                    document.querySelector('.confirm').classList.replace('hide', 'show');
                    document.querySelector('#fault-type-new').innerText = e.target.innerText;
                    document.querySelector('#change').focus({preventScroll: false});
                }

                function submitForm() {
                    console.log('submitForm - iddevices: '
                            + document.querySelector('#iddevices').value
                            + ' / fault_type: '
                            + document.querySelector('#fault_type').value);
                    document.forms['log-task'].submit();
                }

            }, false);
        </script>
         <script>
            document.addEventListener(`DOMContentLoaded`, async () => {

                document.querySelector('.dropdown-trigger').addEventListener('click', function (e) {
                    e.preventDefault();
                    document.getElementById('user').classList.toggle('is-active');
                });

                document.getElementById('user').addEventListener('mouseleave', e => {
                    document.getElementById('user').classList.remove('is-active');
                });

                document.getElementById('btn-info-open').addEventListener('click', function (e) {
                    e.preventDefault();
                    document.getElementById('modal-info').classList.add('is-active');
                }, false);

                document.getElementById('btn-info-close').addEventListener('click', function (e) {
                    e.preventDefault();
                    document.getElementById('modal-info').classList.remove('is-active');
                }, false);

                document.addEventListener("keypress", function (e) {
                    if (e.code == 'KeyI') {
                        e.preventDefault();
                        document.getElementById('btn-info-open').click();
                    } else if (e.code == 'KeyU') {
                        e.preventDefault();
                        document.querySelector('.dropdown-trigger').click();
                    }
                }, false);
                
            }, false);
        </script>

        @endsection
