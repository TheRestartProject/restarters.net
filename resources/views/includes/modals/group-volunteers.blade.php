<!-- Modal -->
<div class="modal fade" id="group-volunteers" tabindex="-1" role="dialog" aria-labelledby="groupVolunteersLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="groupVolunteersLabel">@lang('groups.all_volunteers_group_name_header', ['group' => $group->name])</h5>
        @include('partials.cross')

      </div>

      <div class="modal-body">

        <div class="row">
          <div class="col-md-12 col-lg-7">
            <p>@lang('groups.all_volunteers_group_name_message')</p>
          </div>
        </div>

        <table role="table" class="table table-striped table-hover">
          <thead>
            <tr>
              <th></th>
              <th scope="col">@lang('groups.restarter_column_table')</th>
              <th scope="col">@lang('groups.skills_column_table')</th>
            </tr>
          </thead>
          <tbody>
            @foreach( $view_group->allConfirmedVolunteers as $volunteer )
              @include('partials.volunteer-row')
            @endforeach
          </tbody>
        </table>

        <a href="/group/join/{{ $group->idgroups }}" class="btn btn-primary" id="join-group">@lang('groups.join_group_button')</a>

      </div>


    </div>
  </div>
</div>
