<!-- Modal -->
<style>
    .survey-question {
        background-color: #f7f5ed;
        border: 5px solid #ddd;
        padding: 15px 0 0 15px;
        border-radius: 10px;
    }

    .survey-question h5 {
        font-size: smaller;
        font-weight: bolder;
    }

    .survey-question li {
        list-style: none;
        display: inline;
        font-size: smaller;
        font-weight: normal;
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
                <h5 class="modal-title" style="width:100%" id="tasksurveyModalLabel">@lang('tabicatora.survey.header')</h5>
            </div>
            <div class="modal-body">
                <div class="row text-center">
                    <div class="col survey">
                        <div class="row mb-3 survey-question">
                            <h5>@lang('tabicatora.survey.q1')</h5>
                            <ul>
                                <li>@lang('tabicatora.survey.a1')&nbsp;<input type="radio" name="q1" value="1"></li>
                                <li>@lang('tabicatora.survey.a2')&nbsp;<input type="radio" name="q1" value="2"></li>
                                <li>@lang('tabicatora.survey.a3')&nbsp;<input type="radio" name="q1" value="3"></li>
                                <li>@lang('tabicatora.survey.a4')&nbsp;<input type="radio" name="q1" value="4"></li>
                                <li>@lang('tabicatora.survey.a5')&nbsp;<input type="radio" name="q1" value="5"></li>
                            </ul>
                        </div>
                        <div class="row mb-3 survey-question">
                            <h5>@lang('tabicatora.survey.q2')</h5>
                            <ul>
                                <li>@lang('tabicatora.survey.a1')&nbsp;<input type="radio" name="q2" value="1"></li>
                                <li>@lang('tabicatora.survey.a2')&nbsp;<input type="radio" name="q2" value="2"></li>
                                <li>@lang('tabicatora.survey.a3')&nbsp;<input type="radio" name="q2" value="3"></li>
                                <li>@lang('tabicatora.survey.a4')&nbsp;<input type="radio" name="q2" value="4"></li>
                                <li>@lang('tabicatora.survey.a5')&nbsp;<input type="radio" name="q2" value="5"></li>
                            </ul>
                        </div>
                        <div class="row mb-3 survey-question">
                            <h5>@lang('tabicatora.survey.q3')</h5>
                            <ul>
                                <li>@lang('tabicatora.survey.a1')&nbsp;<input type="radio" name="q3" value="1"></li>
                                <li>@lang('tabicatora.survey.a2')&nbsp;<input type="radio" name="q3" value="2"></li>
                                <li>@lang('tabicatora.survey.a3')&nbsp;<input type="radio" name="q3" value="3"></li>
                                <li>@lang('tabicatora.survey.a4')&nbsp;<input type="radio" name="q3" value="4"></li>
                                <li>@lang('tabicatora.survey.a5')&nbsp;<input type="radio" name="q3" value="5"></li>
                            </ul>
                        </div>
                        <div class="row mb-3 survey-question">
                            <h5>@lang('tabicatora.survey.q4')</h5>
                            <ul>
                                <li>@lang('tabicatora.survey.a1')&nbsp;<input type="radio" name="q4" value="1"></li>
                                <li>@lang('tabicatora.survey.a2')&nbsp;<input type="radio" name="q4" value="2"></li>
                                <li>@lang('tabicatora.survey.a3')&nbsp;<input type="radio" name="q4" value="3"></li>
                                <li>@lang('tabicatora.survey.a4')&nbsp;<input type="radio" name="q4" value="4"></li>
                                <li>@lang('tabicatora.survey.a5')&nbsp;<input type="radio" name="q4" value="5"></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <p class="buttons">
                        <a href="javascript:void(0);" id="btn-send-survey" class="btn btn-md btn-rounded">@lang('tabicatora.survey.send')</a>
                        <button id="btn-skip-survey" type="button" class="btn btn-md btn-rounded">@lang('tabicatora.survey.skip')</button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener(`DOMContentLoaded`, async () => {

        document.getElementById('btn-send-survey').addEventListener('click', function(e) {
            // to do: write submit function
        }, true);

        document.getElementById('btn-skip-survey').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.replace(window.location.href.replace('/survey', '/'));
        }, true);

    }, false);
</script>