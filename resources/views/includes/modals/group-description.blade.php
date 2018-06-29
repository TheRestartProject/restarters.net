<!-- Modal -->
<div class="modal modal__description fade" id="group-description" tabindex="-1" role="dialog" aria-labelledby="groupDescriptionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="groupDescriptionLabel">@lang('groups.about_group_name_header', ['group' => $group->name])</h5>
        @include('partials/cross')

      </div>

      <div class="modal-body">

        {!! $group->free_text !!}

      </div>


    </div>
  </div>
</div>
