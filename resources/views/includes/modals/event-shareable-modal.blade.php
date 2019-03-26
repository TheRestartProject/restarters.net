@if( Auth::check() )
  <!-- Modal -->
  <div class="modal modal__invite fade" id="shareable-modal" tabindex="-1" role="dialog" aria-labelledby="inviteToEventShareableLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header d-flex justify-content-between">
          <h5 id="inviteToEventShareableLabel">@lang('events.invite_restarters_modal_heading')</h5>
          <a href="#" class="align-items-center toggle-invite-modals"><svg width="22" height="13" viewBox="0 0 580 680" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;">
              <g transform="matrix(1.47821,0,0,1.39922,-170.205,256.927)">
                  <g transform="matrix(1,0,0,1,0,-233.234)">
                      <g transform="matrix(1,0,0,1,-5.32682,12.0794)">
                          <circle cx="589.131" cy="91.919" r="51.831" style="fill:rgb(102,107,108);"/>
                      </g>
                      <g transform="matrix(1,0,0,1,-213.517,164.952)">
                          <circle cx="589.131" cy="91.919" r="51.831" style="fill:rgb(102,107,108);"/>
                      </g>
                      <g transform="matrix(1,0,0,1,-418.368,12.0794)">
                          <circle cx="589.131" cy="91.919" r="51.831" style="fill:rgb(102,107,108);"/>
                      </g>
                      <path d="M583.805,52.168L170.763,52.168L138.335,144.578L346.038,299.622L375.614,308.701L403.283,300.83L613.84,146.268L583.805,52.168Z" style="fill:rgb(102,107,108);"/>
                  </g>
                  <g transform="matrix(1,0,0,1,0,-233.234)">
                      <path d="M635.635,169.998C635.635,169.623 635.635,409.743 635.635,409.743C635.635,438.349 612.411,461.574 583.805,461.574C581.536,461.574 579.302,461.428 577.111,461.144L576.242,461.574L170.763,461.574C142.157,461.573 118.933,438.349 118.932,409.743C118.932,409.743 118.932,200.438 118.932,177.949C118.932,176.663 118.932,169.988 118.932,169.998C118.932,170.184 126.526,184.954 138.378,194.456L138.335,194.578L346.038,349.622L346.546,349.778C354.838,355.409 364.845,358.701 375.614,358.701C385.62,358.701 394.967,355.86 402.892,350.941L403.283,350.83L405.223,349.406C405.907,348.929 406.579,348.435 407.239,347.926L613.84,196.268L613.831,196.239C627.024,186.84 635.635,170.374 635.635,169.998Z" style="fill:rgb(102,107,108);"/>
                  </g>
              </g>
          </svg>
          @lang('events.email_invite')</a>
        </div>

        <div class="modal-body">
          <form action="/party/invite" method="post" onkeypress="return event.keyCode != 13;">
            @csrf
            <input type="hidden" name="group_name" value="{{ $formdata->group_name }}">
            <input type="hidden" id="event_id" name="event_id" value="{{ $formdata->id }}">

            <div id="invite_div" class="form-group">
              <label for="shareable_link_box">@lang('events.shareable_link_box'):</label>
              <input type="text" id="shareable_link_box" name="shareable_link_box" class="form-control" autocomplete="off" value="{{ $event->shareable_link }}">
            </div>
            <small class="after-offset">@lang('groups.type_shareable_link_message')</small>

            <div class="d-flex flex-row justify-content-between align-items-center">
              <a href="#" class="close-invite-modal mb-0" data-dismiss="modal">@lang('events.cancel_invites_link')</a>
              <button type="submit" class="btn btn-primary m-0" data-dismiss="modal">@lang('groups.done_button')</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
@endif
