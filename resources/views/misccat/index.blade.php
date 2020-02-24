@extends('layouts.app', ['show_login_join_to_anons' => true])

@section('extra-css')

@include('misccat/shared-css')

@endsection

@section('content')

<section class="misccat">
    <div class="container mt-1 mt-sm-4">
        <div class="row row-compressed">
            <div class="col-6">
                <h1 class="pull-left">MiscCat </h1>
            </div>
            <div class="col-6">
                <!--
            These images are licensed under the Creative Commons Attribution 4.0 International license.
            Attribution: Vincent Le Moign
            https://commons.wikimedia.org/wiki/Category:SVG_emoji_smilies
                -->
                <a id="btn-info-open"
                   data-toggle="modal" data-target="#misccatInfoModal"
                   class="btn btn-info btn-sm btn-rounded p-2">
                    <svg style="width:24px;height:24px;" viewBox="0 0 24 24">
                    <title>About MiscCat</title>
                    <path fill="#fff" d="M13.5,4A1.5,1.5 0 0,0 12,5.5A1.5,1.5 0 0,0 13.5,7A1.5,1.5 0 0,0 15,5.5A1.5,1.5 0 0,0 13.5,4M13.14,8.77C11.95,8.87 8.7,11.46 8.7,11.46C8.5,11.61 8.56,11.6 8.72,11.88C8.88,12.15 8.86,12.17 9.05,12.04C9.25,11.91 9.58,11.7 10.13,11.36C12.25,10 10.47,13.14 9.56,18.43C9.2,21.05 11.56,19.7 12.17,19.3C12.77,18.91 14.38,17.8 14.54,17.69C14.76,17.54 14.6,17.42 14.43,17.17C14.31,17 14.19,17.12 14.19,17.12C13.54,17.55 12.35,18.45 12.19,17.88C12,17.31 13.22,13.4 13.89,10.71C14,10.07 14.3,8.67 13.14,8.77Z"></path>
                    </svg></a>
                <img id="misccat" class="pull-right" src="{{ asset('/images/misccat/100-cat-face-with-wry-smile.svg.png') }}" alt="MiscCat" width="48" height="48" />
            </div>
        </div>
        <?php if ($misc) { ?>
            <div class="row problem p-2 mb-2 mx-1 mx-sm-0 notification">
                <div class="col">
                    <div class="row">
                        <div class="col">
                            <p>
                                <span class="btn btn-md py-1 py-sm-2 btn-misc-info"><?php echo $misc->brand; ?></span>
                                <span class="btn btn-md py-1 py-sm-2 btn-misc-info"><?php echo $misc->model; ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8 offset-sm-2">
                            <p class="subtitle">
                                <?php echo $misc->problem; ?>
                            </p>
                        </div>
                        <div class="col-4 col-sm-2">
                            <button id="btn-translate" class="pull-right btn btn-md px-3 py-1">
                                <a href="https://translate.google.com/#view=home&op=translate&sl=auto&tl=en&text=<?php echo $misc->translate; ?>" target="_blank">
                                    Translate
                                </a>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <form id="log-task" action="misccat" method="POST">
                @csrf
                <div class="container misccat">
                    <div class="container">
                        <input type="hidden" id="iddevices" name="iddevices" value="<?php echo $misc->iddevices; ?>">
                        <input type="hidden" id="category" name="category" value="<?php echo $misc->category; ?>">
                        <input type="hidden" id="eee" name="category" value="1">
                        <p class="buttons is-centered">
                            <button class="btn btn-md btn-success btn-rounded" id="Y">
                                <span class="underline">Y</span>es, leave it as Miscellaneous
                            </button>
                            <button type="submit" name="fetch" id="fetch" class="btn btn-md btn-warning btn-rounded">
                                <span class="">I don't know,&nbsp;<span class="underline">F</span>etch another repair</span>
                            </button>
                            <button type="submit" name="fetch" id="N" class="btn btn-md btn-danger btn-rounded">
                                <span class=""><span class="underline">N</span>ope, let me pick another category</span>
                            </button>
                        </p>
                    </div>
                    <div class="container options hide">
                        <p class="confirm hide">
                            <button class="btn-md btn-info btn-rounded" id="change"><span class="underline">G</span>o with "<span id="category-new"></span>"</button>
                        </p>
                        <div id="eee-radios" class="container">
                            <div>It uses
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" focusable="false" width="1em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 64 64"><path fill="#fbb11c" d="M43.4.159L12.06 28.492l24.31 7.538L18.12 64l35.26-33.426l-18.978-8.464z"/></svg>
                                <span class="electricity">electricity</span>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" focusable="false" width="1em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 64 64"><path fill="#fbb11c" d="M43.4.159L12.06 28.492l24.31 7.538L18.12 64l35.26-33.426l-18.978-8.464z"/></svg>
                                <span>(mains/battery/solar)</span>
                            </div>
                            <label class="radio"><input type="radio" name="eee" value="1" checked><strong class="eee-opt has-text-yellow">YES</strong></label>
                            <label class="radio"><input type="radio" name="eee" value="0"><strong class="eee-opt has-text-grey">NO</strong></label>
                        </div>
                        <div class="cat-is">... and the category IS</div>                        
                        <div class="buttons">
                            <div class="cat-eee hide">
                                <button class="btn btn-sm is-rounded cat-is-selected btn-misc">Miscellaneous</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Clothing/Textile</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Bicycle</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Furniture</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Jewellery</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Tool</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Toy</button>
                            </div>
                            <div class="cat-eee">
                                <button class="btn btn-sm is-rounded cat-is-selected btn-misc">Miscellaneous</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Aircon/Dehumidifier</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Clock/Watch</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Decorative or safety lights</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Desktop computer</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Digital Compact Camera</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">DLSR / Video Camera</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Fan</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Flat screen 15-17"</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Flat screen 19-20"</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Flat screen 22-24"</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Flat screen 26-30"</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Flat screen 32-37"</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Hair & Beauty item</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Handheld entertainment device</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Headphones</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Heater</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Hi-Fi integrated</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Hi-Fi separates</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Iron</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Kettle</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Lamp</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Landline phone</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Laptop large</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Laptop medium</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Laptop small</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Mobile</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Musical instrument</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Paper shredder</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">PC Accessory</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Portable radio</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Power tool</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Printer/scanner</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Projector</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Sewing machine</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Small kitchen item</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Tablet</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Toaster</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Toy</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">TV and gaming-related accessories</button>
                                <button class="btn btn-sm is-rounded cat-is-unselected">Vacuum</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php } ?>

        @include('misccat/info-modal')
</section>

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

        [...document.querySelectorAll('.cat-eee .btn')].forEach(elem => {
            elem.addEventListener('click', function (e) {
                e.preventDefault();
                doOption(e);
            });
        });

        [...document.querySelectorAll('input[name=eee]')].forEach(elem => {
            elem.addEventListener('click', function (e) {
                doEEE(e);
            });
        });

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
            } else if (e.code == 'KeyI') {
                e.preventDefault();
                document.getElementById('btn-info-open').click();
            }
        }, false);

        function doYes(e) {
            document.getElementById('category').value = document.getElementById('category-new').innerText = 'Misc';
            submitForm();
        }

        function doNo(e) {
            document.querySelector('.options').classList.replace('hide', 'show');
        }

        function doChange(e) {
            document.getElementById('category').value = document.getElementById('category-new').innerText;
            if (document.getElementById('category').value == 'Miscellaneous') {
                document.getElementById('category').value = 'Misc';
            }
            submitForm();
        }

        function doEEE(e) {
            [...document.getElementById('eee-radios').querySelectorAll('.eee-opt')].forEach(elem => {
                if (elem.classList.contains('has-text-yellow')) {
                    elem.classList.replace('has-text-yellow', 'has-text-grey');
                } else if (elem.classList.contains('has-text-grey')) {
                    elem.classList.replace('has-text-grey', 'has-text-yellow');
                }
            });

            [...document.querySelectorAll('.cat-eee .btn')].forEach(elem => {
                elem.classList.replace('cat-is-selected', 'cat-is-unselected');
            });
            [...document.querySelectorAll('.cat-eee .btn-misc')].forEach(elem => {
                elem.classList.replace('cat-is-unselected', 'cat-is-selected');
            });

            [...document.querySelectorAll('.cat-eee')].forEach(elem => {
                elem.classList.toggle('hide');
            });
            document.getElementById('eee').value = e.target.value;
        }

        function doOption(e) {
            var cat = e.target.innerText;
            [...document.querySelectorAll('.cat-eee .btn')].forEach(elem => {
                elem.classList.replace('cat-is-selected', 'cat-is-unselected');
                if (elem.innerText == cat) {
                    elem.classList.replace('cat-is-unselected', 'cat-is-selected');
                }
            });
            document.querySelector('.confirm').classList.replace('hide', 'show');
            document.getElementById('category-new').innerText = e.target.innerText;
        }

        function submitForm() {
            console.log('submitForm - iddevices: '
                    + document.getElementById('iddevices').value
                    + ' / eee: '
                    + document.getElementById('eee').value
                    + ' / category: '
                    + document.getElementById('category').value);
            document.forms['log-task'].submit();
        }

    }, false);
</script>

@endsection
