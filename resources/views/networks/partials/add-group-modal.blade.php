<div class="modal fade" id="network-add-group" tabindex="-1" role="dialog" aria-labelledby="addVolunteerEventLabel" aria-hidden="true">
<div class="modal-dialog" role="document">

    <div class="modal-content">

    <div class="modal-header">

        <h5 id="addVolunteerEventLabel">@lang('networks.show.add_groups_modal_header', ['name' => $network->name])</h5>
        @include('partials.cross')

    </div>

    <div class="modal-body">
        <form class="form" action="{{ route('networks.associate-group', ['network' => $network->id]) }}" method="post">

            @csrf
            <label for="groups[]">@lang('networks.show.add_groups_select_label'):</label>
            <select name="groups[]" id="groups[]" class="form-control" multiple size="8" required>
                <option></option>
                @foreach($groupsForAssociating as $group)
                    <option value="{{{ $group->idgroups }}}">{{{ $group->name }}}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary float-right">@lang('networks.show.add_groups_save_button')</button>
        </form>
    </div>
    </div>
</div>
</div>
