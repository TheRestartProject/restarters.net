<!-- Modal -->
<div class="modal modal__invite fade" id="event-invite-to" tabindex="-1" role="dialog" aria-labelledby="inviteToEventLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="inviteToEventLabel">@lang('events.invite_restarters_modal_heading')</h5>
        @include('fixometer/partials/cross')

      </div>

      <div class="modal-body">
        <form action="{{{ prefix_route('event-upcoming') }}}">

          <input type="hidden" name="message" value="invite">

          <p>@lang('events.invite_restarters_modal_description')</p>

          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="invites_to_volunteers">
            <label class="form-check-label" for="invites_to_volunteers">@lang('events.send_invites_to_restarters_tickbox', ['group' => 'The Mighty Restarters'])</label>
          </div>

          <br>

          <div class="form-group">
              <label for="manual_invite_box">@lang('events.manual_invite_box'):</label>
              <input type="text" class="form-control field" id="manual_invite_box" name="manual_invite_box">
          </div>
          <small class="after-offset">@lang('events.type_email_addresses_message')</small>

          <div class="form-group">
              <label for="message_to_restarters">@lang('events.message_to_restarters'):</label>
              <textarea name="message_to_restarters" id="message_to_restarters" class="form-control field">@lang('events.sample_text_message_to_restarters')</textarea>
          </div>

          <button type="submit" class="btn btn-primary float-right">@lang('events.send_invite_button')</button>
        </form>
      </div>


    </div>
  </div>
</div>
