<template>
  <b-modal
      id="calendar"
      v-model="showModal"
      no-stacking
      no-body
      size="lg"
  >
    <template slot="modal-title">
      <div class="d-flex justify-content-between">
        <h5 id="inviteToEventLabel">{{ __('events.invite_restarters_modal_heading') }}</h5>
        <b-button variant="link" click="inviteViaLink" v-if="invitingViaEmail">
          <b-img src="/icons/link_ico.png" />
          __('events.shareable_link')
        </b-button>
        <b-button variant="link" click="inviteViaLink" v-else>
          <b-img src="/images/mail_ico.svg" />
          __('events.email_invite')
        </b-button>
      </div>
    </template>

    <form action="/party/invite" method="post" onkeypress="return event.keyCode != 13;">
      <input type="hidden" name="_token" :value="CSRF" />
      <input type="hidden" name="group_name" value="{{ groupName }}">
      <input type="hidden" id="event_id" name="event_id" value="{{ eventId }}">

      <div id="invite_div" class="form-group">
        <label for="manual_invite_box">{{ __('events.manual_invite_box') }}:</label>
        <input id="manual_invite_box" type="email" inputmode="text" multiple name="manual_invite_box" class="form-control" autocomplete="off" onblur="reportValidity()">
      </div>
      <small class="after-offset">{{ __('events.type_email_addresses_message') }}</small>
      @if( App\Helpers\Fixometer::userHasEditPartyPermission($formdata->id, Auth::user()->id) || App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') )
      <div class="form-check">
        <label class="form-check-label" for="invites_to_volunteers">
          <input type="checkbox" name="invite_group" class="form-check-input" id="invites_to_volunteers" value="1">
          {{ __('events.send_invites_to_restarters_tickbox') }}
        </label>
      </div>
      <br>
      @endif
      <hr/>
      <div class="form-group">
        <label for="message_to_restarters">{{ __('events.message_to_restarters') }}:</label>
        <textarea name="message_to_restarters" id="message_to_restarters" class="form-control field" :placeholder="__('events.sample_text_message_to_restarters')" rows="3"></textarea>
      </div>
    </form>

    <template slot="modal-footer" slot-scope="cancel">
      <div class="d-flex flex-row justify-content-between align-items-center">
        <b-button variant="link" @click="cancel">{{ __('events.cancel_invites_link') }}</b-button>
        <b-button variant="primary" type="submit">{{ __('events.send_invite_button') }}</b-button>
      </div>
    </template>

  </b-modal>


          <div class="collapse multi-collapse-invite-modal">
            <div id="invite_div" class="form-group">
              <label for="shareable_link_box">@lang('events.shareable_link_box'):</label>
              <input type="text" id="shareable_link_box" name="shareable_link_box" class="form-control" autocomplete="off" value="{{ $event->shareable_link }}">
            </div>
            <small class="after-offset">@lang('groups.type_shareable_link_message')</small>

            <div class="d-flex flex-row justify-content-between align-items-center">
              <a href="#" class="text-dark mb-0" data-dismiss="modal">@lang('events.cancel_invites_link')</a>
              <button type="submit" class="btn btn-primary m-0" data-dismiss="modal">@lang('groups.done_button')</button>
            </div>
          </div>
        </div>


      </div>
    </div>
  </div>

</template>
<script>
export default {
  props: {
    groupName: {
      type: String,
      required: true
    },
    eventId: {
      type: Number,
      required: true
    },
  }
  data () {
    return {
      invitingViaEmail: true
    }
  },
  computed: {
    CSRF () {
      return this.$store.getters['auth/CSRF']
    },
  }
}
</script>