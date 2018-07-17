@if( !empty($group_volunteers) )
  <div class="modal fade" id="event-add-volunteer" tabindex="-1" role="dialog" aria-labelledby="addVolunteerEventLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">

      <div class="modal-content">

        <div class="modal-header">

          <h5 id="addVolunteerEventLabel">@lang('events.add_volunteer_modal_heading')</h5>
          @include('partials.cross')

        </div>

        <div class="modal-body">

          <form action="/party/add-volunteer" method="post">

            @csrf

            <input type="hidden" name="event" value="{{{ $event->idevents }}}">

            <div class="form-group">
                <label for="group_member">@lang('events.group_member'):</label>
                <select class="form-control field toggle-manual-invite" id="group_member" name="user">
                  <option>@lang('events.option_default')</option>
                  <option value="not-registered">@lang('events.option_not_registered')</option>
                  @foreach( $group_volunteers as $group_volunteer )
                    <option value="{{{ $group_volunteer->id }}}">{{{ $group_volunteer->name }}}</option>
                  @endforeach
                </select>
            </div>

            <div class="show-hide-manual-invite" style="display: none;">

                <div class="form-group">
                    <label for="full_name">@lang('events.full_name'):</label>
                    <input type="text" class="form-control field" id="full_name" name="full_name" placeholder="@lang('events.full_name_helper')">
                </div>

                <div class="form-group">
                    <label for="volunteer_email_address">@lang('events.volunteer_email_address'):</label>
                    <input type="email" class="form-control field" id="volunteer_email_address" name="volunteer_email_address">
                </div>
                <small class="after-offset">@lang('events.message_volunteer_email_address')</small>

            </div>

            <button type="submit" class="btn btn-primary float-right">@lang('events.volunteer_attended_button')</button>

          </form>

        </div>


      </div>
    </div>
  </div>
@endif
