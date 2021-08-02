<!-- Modal -->
<div class="modal modal-intro modal-form modal-reporting fade" id="time-reporting-modal-1" tabindex="-1" role="dialog" aria-labelledby="time-reporting-modal-1-Label" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>

        <div class="modal-body">
            <div class="row">
                <div class="col">

                    <h5 id="time-reporting-modal-1-Label">@lang('reporting.breakdown_by_country')</h5>
                    <p>@lang('reporting.breakdown_by_country_content')</p>

                </div>
            </div>

                    <table class="table table-striped" role="table">
                        <thead>
                            <tr>
                                <th>@lang('reporting.country_name')</th>
                                <th>@lang('reporting.total_hours')</th>
                            </tr>
                        </thead>
                        <tbody>
                          @foreach($all_country_hours_completed as $country_hours)
                            <tr>
                              @if(!is_null($country_hours->country))
                                <td>{{ App\Helpers\Fixometer::getCountryFromCountryCode($country_hours->country) }}</td>
                              @else
                                <td>N/A</td>
                              @endif
                              <td>{{ substr($country_hours->event_hours, 0, -4) }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                    </table>
        </div>


    </div>
  </div>
</div>
