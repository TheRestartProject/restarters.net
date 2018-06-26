<!-- Modal -->
<div class="modal modal-intro fade" id="group" tabindex="-1" role="dialog" aria-labelledby="groupLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <img src="{{ url('/images/groups-modal.jpg') }}" alt="Workshop">

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>

        <div class="modal-body">

          <h5 id="groupLabel">@lang('groups.groups_modal_title')</h5>
          <p>@lang('groups.groups_modal_content')</p>

          <a href="{{{ prefix_route('create-group') }}}" class="btn btn-primary">@lang('groups.groups_modal-button')</a>

        </div>


    </div>
  </div>
</div>
