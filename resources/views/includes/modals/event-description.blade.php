<!-- Modal -->
<div class="modal modal__description fade" id="event-description" tabindex="-1" role="dialog" aria-labelledby="eventDescriptionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="eventDescriptionLabel">@lang('events.about_event_name_header', ['event' => $formdata->venue])</h5>
        @include('partials.cross')

      </div>

      <div class="modal-body">

        {!! $formdata->free_text !!}

      </div>

    </div>
  </div>
</div>
