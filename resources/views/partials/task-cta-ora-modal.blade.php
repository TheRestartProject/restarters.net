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
        background-color: white;
        border: 2px solid #0faca9;
        color: #0faca9;
    }
</style>
<div class="modal fade" data-backdrop="static" data-keyboard="false" id="taskctaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content microtask">
            <div class="modal-header">
                <h5 class="modal-title" style="width:100%" id="taskctaModalLabel">@lang('microtask-ora.cta.header')</h5>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col">
                        <div class="row">
                            <div class="col">
                                <p>@lang('microtask-ora.cta.body-s1-p1')</p>
                                <p><strong>@lang('microtask-ora.cta.body-s1-p2')</strong></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-lg-4 mb-2 mb-lg-0">
                                <div class="use-case">
                                    <h5>@lang('microtask-ora.cta.body-s2-header')</h5>
                                    <p>@lang('microtask-ora.cta.body-s2-p1')</p>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 mb-2 mb-lg-0">
                                <div class="use-case">
                                    <h5>@lang('microtask-ora.cta.body-s3-header')</h5>
                                    <p>@lang('microtask-ora.cta.body-s3-p1')</p>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 mb-2 mb-lg-0">
                                <div class="use-case">
                                    <h5>@lang('microtask-ora.cta.body-s4-header')</h5>
                                    <p>@lang('microtask-ora.cta.body-s4-p1')</p>
                                </div>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="col">
                                <h5>@lang('microtask-ora.cta.body-s5-header')</h5>
                                <p>The <a href="https://openrepair.org/get-involved" target="_blank">Open Repair Alliance</a> @lang('microtask-ora.cta.body-whois-ora')</p>
                                <p><a href="https://therestartproject.org/" target="_blank">The Restart Project</a> @lang('microtask-ora.cta.body-whois-trp')</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <p class="buttons">
                        <a href="javascript:void(0);" id="btn-join" class="btn btn-md btn-rounded" target="_blank">@lang('microtask-ora.cta.btn-signup')</a>
                        <button id="btn-skip" type="button" class="btn btn-md btn-rounded">@lang('microtask-ora.cta.btn-notnow')</button>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener(`DOMContentLoaded`, async () => {

            document.getElementById('btn-join').addEventListener('click', function (e) {
                e.preventDefault();
                window.location.replace('{{ url('/user/register/') }}');
            }, true);

            document.getElementById('btn-skip').addEventListener('click', function (e) {
                e.preventDefault();
                window.location.replace(window.location.href.replace('/cta', '/'));
            }, true);

        }, false);
    </script>

