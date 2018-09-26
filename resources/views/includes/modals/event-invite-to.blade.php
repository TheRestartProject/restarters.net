@if( Auth::check() )
<!-- Modal -->
<div class="modal modal__invite fade" id="event-invite-to" tabindex="-1" role="dialog" aria-labelledby="inviteToEventLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="inviteToEventLabel">@lang('events.invite_restarters_modal_heading')</h5>
        @include('partials.cross')

      </div>

      <div class="modal-body">

        <p>@lang('events.invite_restarters_modal_description')</p>

        <hr/>

        <form action="/party/invite" method="post" onkeypress="return event.keyCode != 13;">
          @csrf

          <input type="hidden" name="group_name" value="{{ $formdata->group_name }}">
          <input type="hidden" id="event_id" name="event_id" value="{{ $formdata->id }}">

          <div id="invite_div" class="form-group">
              <label for="manual_invite_box">@lang('events.manual_invite_box'):</label>
              <input type="text" id="manual_invite_box" name="manual_invite_box" class="tokenfield form-control" autocomplete="off">
          </div>
          @if( FixometerHelper::userHasEditPartyPermission($formdata->id, Auth::user()->id) || FixometerHelper::hasRole(Auth::user(), 'Administrator') )
              <div class="form-check">
                  <label class="form-check-label" for="invites_to_volunteers">
                      <input type="checkbox" name="invite_group" class="form-check-input" id="invites_to_volunteers" value="1">
                      @lang('events.send_invites_to_restarters_tickbox', ['group' => $formdata->group_name])
                  </label>
              </div>
              <br>
          @endif

          <small class="after-offset">@lang('events.type_email_addresses_message')</small>

          <hr/>

          <div class="form-group">
              <label for="message_to_restarters">@lang('events.message_to_restarters'):</label>
              <textarea name="message_to_restarters" id="message_to_restarters" class="form-control field" placeholder="@lang('events.sample_text_message_to_restarters')"></textarea>
              <small class="form-text text-muted">@lang('events.message_explainer')</small>
          </div>

          <button disabled type="submit" class="btn btn-primary float-right">@lang('events.send_invite_button')</button>
        </form>

      </div>


    </div>
  </div>
</div>
@endif
