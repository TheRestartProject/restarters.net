<!-- Modal -->
<div class="modal fade" id="event-all-volunteers" tabindex="-1" role="dialog" aria-labelledby="eventAllVolunteersLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="eventAllVolunteersLabel">@lang('events.all_invited_restarters_modal_heading')</h5>
        @include('fixometer/partials/cross')

      </div>

      <div class="modal-body">

        <div class="row">
          <div class="col-md-12 col-lg-7">
            <p>@lang('events.all_invited_restarters_modal_description')</p>
          </div>
        </div>

        <table role="table" class="table table-striped table-hover">
          <thead>
            <tr>
              <th></th>
              <th scope="col">@lang('events.table_restarter_column')</th>
              <th scope="col">@lang('events.table_skills_column')</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="table-cell-icon"><img src="{{{ url('images/placeholder.png') }}}" class="rounded" alt="Placeholder"></td>
              <td><a href="{{{ prefix_route('profile') }}}">Dean Appleton-Claydon</a></td>
              <td>
                Communication<br>
                Communication
              </td>
            </tr>
          </tbody>
        </table>

      </div>

    </div>
  </div>
</div>
