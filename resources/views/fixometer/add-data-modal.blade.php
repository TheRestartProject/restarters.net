<!-- Modal -->
<div class="modal modal-intro modal-brand modal-form fade" id="add-device-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content p-0">

            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>

            <div class="card card-info-box bg-white mt-auto">
                <div class="card-body text-dark pt-0">
                    <div class="d-flex flex-row align-items-center">
                        <h2 class="mb-0 mr-30 mt-10">
                            @lang('devices.add_data_title')
                        </h2>

                        <div class="mr-auto">
                            @include('svgs.fixometer.fixometer-doodle', [
                            'offset_top' => 10,
                            'height' => 66,
                            ])
                        </div>
                    </div>

                    <hr class="hr-dashed mb-25 mt-10">

                    <p>
                        @lang('devices.add_data_description')
                    </p>

                    <div class="flex-dynamic-row">
                        <div class="flex-dynamic mb-20 mb-md-1">
                            <label for="items_cat" class="sr-only">@lang('devices.group'):<</label> <div class="form-control form-control__select">
                                    <select id="group" name="group" class="form-control select2 change-group" title="Choose group...">
                                        @if( ! $user_groups->isEmpty() )
                                        @foreach($user_groups as $group)
                                        <option value="{{ $group->idgroups }}">
                                            {{ $group->name }}
                                        </option>
                                        @endforeach
                                        @endif
                                    </select>
                        </div>
                    </div>

                    <div class="flex-dynamic mb-20">
                        <label for="items_cat" class="sr-only">@lang('devices.category'):</label>
                        <div class="form-control form-control__select">
                            <select id="events" name="events" class="form-control select2 change-events" title="Choose event...">
                            </select>
                        </div>
                    </div>

                    <a href="#" class="ml-auto btn btn-primary btn-sm change-event-url">
                        @lang('devices.add_data_action_button')
                    </a>
                </div>
            </div>
        </div>


    </div>
</div>
</div>
