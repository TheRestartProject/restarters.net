<!-- Modal -->
<style>
    .has-background-gold {background-color: #F69B05;}
    .has-background-teal {background-color: #21ACA7;}
    .has-background-pink {background-color: #EC2473;}
    .has-background-green {background-color: #16A765;}
    .has-text-white {color: #FFF;}
    .has-text-bold {font-weight: bold;}
    .microtask ul, li {list-style-type: none;}
    .use-case {
        background-color: #f7f5ed;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 10px;
        height: 100%;
    }
    #btn-join {
        background-color: #0faca8;
        color: white;
    }
    #btn-skip {
        background-color: #white;
        border: 2px solid #0faca9;
        color: #0faca9;
    }
</style>
<div class="modal fade" data-backdrop="static" data-keyboard="false" id="taskctaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content microtask">
            <div class="modal-header">
                <h5 class="modal-title" style="width:100%" id="taskctaModalLabel">Thank you for your help!</h5>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col">
                        <div class="row">
                            <div class="col">
                                <p>Each item you're seeing was brought to a repair event to be fixed rather than thrown away.</p>
                                <p><strong>What do we do with this data?</strong></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-lg-4 mb-2 mb-lg-0">
                                <div class="use-case">
                                    <h5>Share repair skills</h5>
                                    <p>By learning why items break and how to fix them, we can share this knowledge with the world.</p>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 mb-2 mb-lg-0">
                                <div class="use-case">
                                    <h5>Showcase the benefits of repair</h5>
                                    <p>We help groups understand their impact, motivate their community, and support funding bids.</p>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 mb-2 mb-lg-0">
                                <div class="use-case">
                                    <h5>Campaign for change</h5>
                                    <p>Repair data supports policy on the Right to Repair, making products more repairable, and reducing the environmental impact of electronics.</p>
                                </div>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="col">
                                <h5>Want to keep up with our work?</h5>
                                <p>
                                    Sign up to the <a href="https://openrepair.org/get-involved" target="_blank">Open Repair Alliance</a> newsletter to get updates on our work on data.
                                </p>
                                <p>
                                    <a href="https://therestartproject.org/" target="_blank">The Restart Project</a> prototyped this app, you can follow and contribute to discussions on repair data from repair events on their <a href="https://talk.restarters.net/t/our-work-on-repair-data/1150" target="_blank">community platform</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <p class="buttons">
                        <a href="/about" id="btn-join" class="btn btn-md btn-rounded">Join the community</a>
                        <button id="btn-skip" type="button" class="btn btn-md btn-rounded">Not now</button>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener(`DOMContentLoaded`, async () => {

            document.getElementById('btn-join').addEventListener('click', function (e) {
                e.preventDefault();
                window.open("/about", '_blank');
                document.getElementById('btn-skip').click();
            }, true);

            document.getElementById('btn-skip').addEventListener('click', function (e) {
                e.preventDefault();
                window.location.replace(window.location.href.replace('/cta', '/'));
            }, true);

        }, false);
    </script>

