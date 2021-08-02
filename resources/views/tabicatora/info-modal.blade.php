<!-- Modal -->
<div class="modal fade" id="tabicatoraInfoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tabicatoraInfoModalLabel">@lang('tabicatora.info.title')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5>@lang('tabicatora.info.body-s1-header')</h5>
                <p>@lang('tabicatora.info.body-s1-p1')</p>
                <p>@lang('tabicatora.info.body-s1-p2')</p>
                <hr>
                <h5>@lang('tabicatora.info.body-s2-header')</h5>
                <p>@lang('tabicatora.info.body-s2-p1')</p>
                <hr>
                <h5>@lang('tabicatora.info.body-s3-header')</h5>
                <p>@lang('tabicatora.info.body-s3-p1', ['url' => 'https://openrepair.org/about/'])</p>
                <hr>
                <h5>@lang('tabicatora.info.body-s4-header')</h5>
                <p>@lang('tabicatora.info.body-s4-p1', ['url' => env('DISCOURSE_URL') . 't/t/5030'])</p>
            </div>
        </div>
    </div>
</div>

