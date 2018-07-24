<!-- Modal -->
<div class="modal modal-intro modal-form modal-reporting fade" id="time-reporting-modal-2" tabindex="-1" role="dialog" aria-labelledby="time-reporting-modal-2-Label" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>

        <div class="modal-body">

            <div class="row">
                <div class="col-md-6">

                <h5 id="time-reporting-modal-2-Label">@lang('reporting.breakdown_by_city')</h5>
                <p>@lang('reporting.breakdown_by_city_content')</p>

                </div>
            </div>

                    <table class="table table-striped" role="table">
                        <thead>
                            <tr>
                                <th>@lang('reporting.town_city_name')</th>
                                <th>@lang('reporting.total_hours')</th>
                            </tr>
                        </thead>
                        <tbody>
                          @foreach($all_city_hours_completed as $city_hours)
                            <tr>
                              @if(!is_null($city_hours->location))
                                <td>{{ $city_hours->location }}</td>
                              @else
                                <td>N/A</td>
                              @endif
                              <td>{{ substr($city_hours->event_hours, 0, -4) }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                    </table>

        </div>


    </div>
  </div>
</div>
