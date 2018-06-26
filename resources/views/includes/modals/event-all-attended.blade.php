<!-- Modal -->
<div class="modal fade" id="event-all-attended" tabindex="-1" role="dialog" aria-labelledby="eventAllVolunteersLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        @if( isset($confirmed) && $confirmed == true )
          <h5 id="eventAllVolunteersLabel">All Restarters confirmed</h5>
        @else
          <h5 id="eventAllVolunteersLabel">@lang('events.all_restarters_attended_modal_heading')</h5>
        @endif
        @include('partials.cross')

      </div>

      <div class="modal-body">

        <div class="row">
          <div class="col-md-12 col-lg-7">
            <p>@lang('events.all_restarters_attended_modal_description')</p>
          </div>
        </div>

        <table role="table" class="table table-striped table-hover">
          <thead>
            <tr>
              <th></th>
              <th scope="col">@lang('events.table_restarter_column')</th>
              <th scope="col">@lang('events.table_skills_column')</th>
              @if( !isset($confirmed) )
                <th></th>
              @endif
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="table-cell-icon"><img src="{{{ url('images/placeholder.png') }}}" class="rounded" alt="Placeholder"></td>
              <td><a href="">Dean Appleton-Claydon</a> <span class="badge badge-primary">Host</span></td>
              <td>
                Communication<br>
                Communication
              </td>
              @if( !isset($confirmed) )
                <td align="right"><a href="#alert">@lang('events.remove_volunteer_link')</a></td>
              @endif
            </tr>
          </tbody>
        </table>

      </div>

    </div>
  </div>
</div>
