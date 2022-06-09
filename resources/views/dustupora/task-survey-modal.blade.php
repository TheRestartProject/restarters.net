<!-- Modal -->
<style>
    .survey-question {
        background-color: #f7f5ed;
        border: 5px solid #ddd;
        padding: 15px 0;
        border-radius: 10px;
    }

    .survey-question ul {
        display: flex;
        align-items: stretch;
        justify-content: space-between;
        width: 98%;
        margin: 0;
        padding: 0 1%;
    }

    .survey-question h5 {
        font-size: smaller;
        padding-left: 15px;
    }

    .survey-question li {
        display: block;
        flex: 0 1 auto;
    }

    .survey-question li label {
        font-size: smaller;
    }

    .survey-question li,
    .survey-question input {
        cursor: pointer;
    }

    .survey .error {
        border: 1px solid lightpink;
    }

    #btn-send-survey {
        background-color: #0faca8;
        color: white;
    }

    #btn-skip-survey {
        background-color: white;
        border: 2px solid #0faca9;
        color: #0faca9;
    }
</style>
<div class="modal fade" data-backdrop="static" data-keyboard="false" id="tasksurveyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content microtask">
            <div class="modal-header">
                <h5 class="modal-title" style="width:100%" id="tasksurveyModalLabel">@lang('dustupora.survey.header1')</h5>
            </div>
            <form id="form-survey" action="/dustup" method="POST">
                @csrf
                <input type="hidden" name="task-survey" value="">
                <div class="modal-body">
                    <p class="modal-subtitle" style="width:100%" id="tasksurveyModalLabel">@lang('dustupora.survey.header2')</p>
                    <div class="row">
                        <div class="col survey">
                            <p id="error" class="hide alert information-alert banner alert-secondary">@lang('dustupora.survey.invalid')</p>
                            <div id="q1" class="row mb-3 survey-question">
                                <h5 class="text-center font-weight-bolder">@lang('dustupora.survey.q1')</h5>
                                <ul class="list-inline">
                                    <li class="list-inline-item"><input type="radio" name="q1" value="1">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a1')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q1" value="2">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a2')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q1" value="3">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a3')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q1" value="4">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a4')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q1" value="5">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a5')</label></li>
                                </ul>
                            </div>
                            <div id="q2" class="row mb-3 survey-question">
                                <h5 class="text-center font-weight-bolder">@lang('dustupora.survey.q2')</h5>
                                <ul class="list-inline">
                                    <li class="list-inline-item"><input type="radio" name="q2" value="1">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a1')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q2" value="2">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a2')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q2" value="3">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a3')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q2" value="4">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a4')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q2" value="5">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a5')</label></li>
                                </ul>
                            </div>
                            <div id="q3" class="row mb-3 survey-question">
                                <h5 class="text-center font-weight-bolder">@lang('dustupora.survey.q3')</h5>
                                <ul class="list-inline">
                                    <li class="list-inline-item"><input type="radio" name="q3" value="1">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a1')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q3" value="2">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a2')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q3" value="3">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a3')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q3" value="4">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a4')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q3" value="5">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a5')</label></li>
                                </ul>
                            </div>
                            <div id="q4" class="row mb-3 survey-question">
                                <h5 class="text-center font-weight-bolder">@lang('dustupora.survey.q4')</h5>
                                <ul class="list-inline">
                                    <li class="list-inline-item"><input type="radio" name="q4" value="1">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a1')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q4" value="2">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a2')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q4" value="3">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a3')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q4" value="4">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a4')</label></li>
                                    <li class="list-inline-item"><input type="radio" name="q4" value="5">&nbsp;<label class="font-weight-normal">@lang('dustupora.survey.a5')</label></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <p class="buttons">
                        <a href="javascript:void(0);" id="btn-send-survey" class="btn btn-md btn-rounded">@lang('dustupora.survey.send')</a>
                        <button id="btn-skip-survey" type="button" class="btn btn-md btn-rounded">@lang('dustupora.survey.skip')</button>
                    </p>
                </div>
                <p>@lang('dustupora.survey.footer')</p>
            </form>
        </div>
    </div>
</div>
</div>
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        [...document.querySelectorAll('.survey-question li')].forEach(elem => {
            elem.addEventListener('click', function(e) {
                if (e.srcElement.nodeName == 'LI') {
                    e.srcElement.firstChild.checked = true;
                    document.getElementById(e.srcElement.firstChild.name).classList.remove('error');
                    document.getElementById('error').classList.add('hide');
                }
            });
        });

        [...document.querySelectorAll('.survey-question input')].forEach(elem => {
            elem.addEventListener('change', function(e) {
                document.getElementById(e.srcElement.name).classList.remove('error');
                document.getElementById('error').classList.add('hide');
            });
        });

        document.getElementById('btn-send-survey').addEventListener('click', function(e) {
            e.preventDefault();
            let submit = [];
            for (let i = 1; i < 5; i++) {
                let q = 'q' + i;
                let elem = document.querySelector('input[name="' + q + '"]:checked');
                if (elem == null) {
                    document.getElementById(q).classList.add('error');
                    document.getElementById('error').classList.remove('hide');
                } else {
                    submit.push(elem.value);
                }
            }
            if (submit.length == 4) {
                document.forms['form-survey'].submit();
            }
        }, true);

        document.getElementById('btn-skip-survey').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.replace(window.location.href.replace('/survey', '/'));
        }, true);

    }, false);
</script>