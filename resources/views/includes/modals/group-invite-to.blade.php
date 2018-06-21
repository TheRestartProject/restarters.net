<!-- Modal -->
<div class="modal modal__invite fade" id="invite-to-group" tabindex="-1" role="dialog" aria-labelledby="inviteToGroupLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="inviteToGroupLabel">@lang('groups.invite_group_name_header', ['group' => 'The Mighty Restarters'])</h5>
        @include('fixometer/partials/cross')

      </div>

      <div class="modal-body">

        <p>@lang('groups.invite_group_name_message')</p>

        <div class="form-group">
            <label for="invite_to_group_email_address">@lang('groups.email_addresses_field'):</label>
            <input type="text" class="form-control field select2" id="invite_to_group_email_address" name="invite_to_group_email_address">
        </div>
        <small class="after-offset">@lang('groups.type_email_addresses_message')</small>

        <div class="form-group">
            <label for="invite_to_group_message">@lang('groups.message_header'):</label>
            <textarea name="invite_to_group_message" id="invite_to_group_message" class="form-control field">@lang('groups.message_example_text')</textarea>
        </div>

        <button type="submit" class="btn btn-primary float-right">@lang('groups.send_invite_button')</button>

      </div>


    </div>
  </div>
</div>
