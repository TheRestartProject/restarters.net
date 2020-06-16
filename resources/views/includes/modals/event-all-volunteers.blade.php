<!-- Modal -->
<div class="modal fade" id="event-all-volunteers" tabindex="-1" role="dialog" aria-labelledby="eventAllVolunteersLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="eventAllVolunteersLabel">@lang('events.all_invited_restarters_modal_heading')</h5>
        @include('partials.cross')

      </div>

      <div class="modal-body">

        <div class="row">
          <div class="col-md-12">
            <p>@lang('events.all_invited_restarters_modal_description')</p>
          </div>
        </div>

        <table role="table" class="table table-striped table-hover">
          <thead>
            <tr>
              <th></th>
              <th scope="col">@lang('events.table_restarter_column')</th>
              <th scope="col">@lang('events.table_skills_column')</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($invited as $volunteer)
              @include('partials.volunteer-row', ['type' => 'invited'])
            @endforeach
          </tbody>
        </table>

      </div>

    </div>
  </div>
</div>
