<!-- Modal -->
<div class="modal modal__invite fade" id="invite-to-group" tabindex="-1" role="dialog" aria-labelledby="inviteToGroupLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="inviteToGroupLabel">@lang('groups.invite_group_name_header', ['group' => 'The Mighty Restarters'])</h5>
        @include('partials.cross')

      </div>

      <div class="modal-body">

        <p>@lang('groups.invite_group_name_message')</p>

        <form action="/group/invite" method="post">
          @csrf

          <input type="hidden" name="from_id" value="{{ Auth::user()->id }}">
          <input type="hidden" name="group_name" value="{{ $group->name }}">
          <input type="hidden" id="group_id" name="group_id" value="{{ $group->idgroups }}">

          <div id="invite_div" class="form-group">
              <label for="manual_invite_box">@lang('groups.email_addresses_field'):</label>
              <input type="text" class="form-control tokenfield-make" id="manual_invite_box" name="manual_invite_box"/>
          </div>
          <small class="after-offset">@lang('groups.type_email_addresses_message')</small>

          <div class="form-group">
              <label for="message_to_restarters">@lang('groups.message_header'):</label>
              <textarea name="message_to_restarters" id="message_to_restarters" class="form-control field" placeholder="@lang('groups.message_example_text')"></textarea>
          </div>

          <button type="submit" class="btn btn-primary float-right">@lang('groups.send_invite_button')</button>
        </form>

        <!-- <form action="#">

          <input type="hidden" name="message" value="invite">

          <p>@lang('groups.invite_group_name_message')</p>

          <div class="form-group">
              <label for="invite_to_group_email_address">@lang('groups.email_addresses_field'):</label>
              <input type="email" class="form-control field" id="invite_to_group_email_address" name="invite_to_group_email_address">
          </div>
          <small class="after-offset">@lang('groups.type_email_addresses_message')</small>

          <div class="form-group">
              <label for="invite_to_group_message">@lang('groups.message_header'):</label>
              <textarea name="invite_to_group_message" id="invite_to_group_message" class="form-control field">@lang('groups.message_example_text')</textarea>
          </div>

          <button type="submit" class="btn btn-primary float-right">@lang('groups.send_invite_button')</button> -->

        </div>

      </div>


    </div>
  </div>
</div>
