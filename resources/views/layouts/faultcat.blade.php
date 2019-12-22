<!DOCTYPE html>
<html lang="en" class="has-background-white-bis has-text-black-bis">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <title>FaultCat</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.5/css/bulma.min.css" integrity="sha256-vK3UTo/8wHbaUn+dTQD0X6dzidqc5l7gczvH+Bnowwk=" crossorigin="anonymous" />
        <style>
            body {text-align : center;}
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
    </head>
    <body>
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
                    <div class="container content">
                        @yield('content-modal')                        
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
        <div class="hero is-centered">
            <div class="hero-head has-background-warning fc-margin-bottom">
                <div class="container content">
                    <div class="columns is-flex-mobile is-flex-tablet fc-center">
                        @yield('hero-head')
                    </div>
                </div>
            </div>
            <div class="hero-foot">
                @yield('hero-foot')
            </div>
        </div>
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
        @yield('script')
    </body>
</html>