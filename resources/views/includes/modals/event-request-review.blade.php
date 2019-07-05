<div class="modal fade" id="event-request-review" tabindex="-1" role="dialog" aria-labelledby="requestReviewEventLabel" aria-hidden="true">
<div class="modal-dialog" role="document">

  <div class="modal-content">

    <div class="modal-header">

      <h5 id="requestReviewEventLabel">@lang('events.request_review_modal_heading')</h5>
      @include('partials.cross')

    </div>

    <div class="modal-body">
        <p>
            @lang('events.request_review_message')
        </p>

        <div class="float-right">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('events.cancel_requests')</button>
            <a href="/party/contribution/{{ $event->idevents }}" type="submit" class="btn btn-primary">@lang('events.send_requests')</a>
        </div>
    </div>


  </div>
</div>
</div>