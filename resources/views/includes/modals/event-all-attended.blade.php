<!-- Modal -->
<div class="modal fade" id="event-all-attended" tabindex="-1" role="dialog" aria-labelledby="eventAllVolunteersLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="eventAllVolunteersLabel">@if( $event->hasFinished() ) @lang('events.all_restarters_attended_modal_heading') @else @lang('events.all_restarters_confirmed_modal_heading') @endif</h5>
        @include('partials.cross')

      </div>

      <div class="modal-body">

        <div class="row">
          <div class="col-md-12">
            <p>@lang('events.all_restarters_attended_modal_description')</p>
          </div>
        </div>

        <table role="table" class="table table-striped table-hover">
          <thead>
            <tr>
              <th></th>
              <th scope="col">@lang('events.table_restarter_column')</th>
              <th scope="col">@lang('events.table_skills_column')</th>
              @if ( ( App\Helpers\Fixometer::hasRole(Auth::user(), 'Host') && App\Helpers\Fixometer::userHasEditPartyPermission($formdata->id, Auth::user()->id) ) || App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator'))
                <th></th>
              @endif
            </tr>
          </thead>
          <tbody>
            @foreach ($attended as $volunteer)
              @include('partials.volunteer-row', ['type' => 'attended'])
            @endforeach
          </tbody>
        </table>

      </div>

    </div>
  </div>
</div>
